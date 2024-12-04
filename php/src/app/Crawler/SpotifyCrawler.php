<?php

namespace App\Crawler;

use App\Enums\CacheKeyEnum;
use App\Enums\CrawlerResultEnum;
use App\Enums\ServiceEnum;
use App\Factory;
use App\Session\SessionHandler;
use Exception;
use Illuminate\Console\Concerns\InteractsWithIO;
use Illuminate\Support\Facades\Cache;
use InfluxDB2\WriteApi;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\SpotifyWebAPIException;
use stdClass;

class SpotifyCrawler implements CrawlerInterface
{
    use InteractsWithIO;

    public function __construct(
        protected WriteApi $writeApi,
        protected SessionHandler $sessionHandler,
        protected Factory $factory,
    ) {
    }

    /**
     * @param string|null $username
     * @param array|null  $params array key 'code' => used for requesting the access token authorization code flow
     *
     * @return CrawlerResultEnum
     */
    public function initialSetup(?string $username = null, ?array $params = []): CrawlerResultEnum
    {
        $spotifySession = $this->factory->getSpotifySession();

        /** @var Session $session */
        $session = $spotifySession->getUnderlyingObject();

        if ($username && $this->sessionHandler->sessionExists(ServiceEnum::Spotify, $username)) {
            return CrawlerResultEnum::SESSION_ALREADY_EXISTS;
        }

        $accessTokenCreated = false;
        try {
            $accessTokenCreated = $session->requestAccessToken($params['code']);
        } catch (SpotifyWebAPIException $exception) {
        } finally {
            if (!$accessTokenCreated) {
                return CrawlerResultEnum::SESSION_ACCESS_TOKEN_ERROR;
            }
        }

        if (!$username) {
            $spotifyWebAPI = $this->factory->getSpotifyWebAPI($session);
            $username = $spotifyWebAPI->me()->id;
            session([$this->getType() . '_username' => $username]);
        }

        $this->sessionHandler->saveSession($spotifySession, $username);

        return CrawlerResultEnum::SESSION_SETUP_SUCCESS;
    }

    public function getType(): string
    {
        return ServiceEnum::Spotify->value;
    }

    /**
     * @throws Exception
     */
    public function crawlAll(string $username): void
    {
        $spotifySession = $this->sessionHandler->loadSession(ServiceEnum::Spotify, $username);

        /** @var Session $session */
        $session = $spotifySession->getUnderlyingObject();
        $spotifyWebApi = $this->factory->getSpotifyWebAPI($session);

        logs('crawler')->info("starting spotify crawler for username $username");
        $this->crawlTrackHistoryAndAudioFeatures($username, $spotifyWebApi);
        logs('crawler')->info("finished spotify crawler for username $username");

        $this->sessionHandler->saveSession($spotifySession, $username);
    }

    /**
     * @throws Exception
     */
    protected function crawlTrackHistoryAndAudioFeatures(string $username, SpotifyWebAPI $spotifyWebApi): void
    {
        // TODO add "after" instead of limit if last crawl was last hour
	    $recentTracks = $spotifyWebApi->getMyRecentTracks([
            'limit' => config('services.spotify.crawl_bulk_limit'),
        ])->items;
        logs('crawler')->info("retrieved recent logs for user $username");

        $recentTracksIds = [];
        foreach ($recentTracks as $recentTrack) {
            // automatic deduplication by key
            $recentTracksIds[$recentTrack->track->id] = $recentTrack->track->id;
        }

        $artistIds = [];
        foreach ($recentTracks as $recentTrack) {
            foreach ($recentTrack->track->artists as $artist) {
                // automatic deduplication by key
                $artistIds[$artist->id] = $artist->id;
            }
        }
        logs('crawler')->debug("getting artists for user $username");
        $artistsById = $this->getArtistsById($spotifyWebApi, $artistIds);
        logs('crawler')->debug("getting audio features for user $username");
        $audioFeatures = $this->getAudioFeatures($spotifyWebApi, $recentTracksIds);
        logs('crawler')->debug("writing history for user $username");
        foreach ($recentTracks as $recentTrack) {
            $this->writeTrackHistoryPoint($audioFeatures[$recentTrack->track->id] ?? null, $recentTrack, $username);
            $this->writeGenreHistoryPoints($recentTrack, $artistsById, $username);
        }

        $this->writeApi->close();
    }

    /**
     * @param SpotifyWebAPI         $spotifyWebApi
     * @param array<string, string> $artistIds
     *
     * @return array<string, stdClass>
     */
    protected function getArtistsById(SpotifyWebAPI $spotifyWebApi, array $artistIds): array
    {
        logs('crawler')->debug("getting artists for user $username");
        $artistsFromAPI = $this->getCachedArtistsAndCleanupIds($artistIds);

        $cache = Cache::tags([
            ServiceEnum::Spotify->value,
            ServiceEnum::Spotify->value . CacheKeyEnum::CACHE_KEY_SEPARATOR .  CacheKeyEnum::Artist->value
        ]);

        $newArtistIdCount = count($artistIds);
        if (newArtistIdCount > 0) {
            logs('crawler')->debug("retrieving uncached data for $newArtistIdCount artists");
            // artistIds count could be more than crawl_bulk_limit
            $artistIdsChunks = array_chunk($artistIds, config('services.spotify.crawl_bulk_limit'));

            foreach ($artistIdsChunks as $artistIdsChunk) {
                $response = $spotifyWebApi->getArtists($artistIdsChunk);

                foreach ($response->artists as $artist) {
                    $cache->put($artist->id, $artist, config('services.spotify.cache_ttl'));
                    logs('crawler')->debug("set artist $artist->id to cache");
                    $artistsFromAPI[] = $artist;
                }
            }
        }

        $artistsById = [];
        foreach ($artistsFromAPI as $artistFromAPI) {
            $artistsById[$artistFromAPI->id] = $artistFromAPI;
        }

        return $artistsById;
    }

    protected function getCachedArtistsAndCleanupIds(array &$artistIds): array
    {
        $cache = Cache::tags([
            ServiceEnum::Spotify->value,
            ServiceEnum::Spotify->value . CacheKeyEnum::CACHE_KEY_SEPARATOR .  CacheKeyEnum::Artist->value
        ]);

        $cachedArtists = [];
        foreach ($artistIds as $artistId) {
            if ($cache->has($artistId)) {
                $cachedArtists[] = $cache->get($artistId);
                unset($artistIds[$artistId]);
                logs('crawler')->debug("found artist $artistId in cache");
            }
        }

        return $cachedArtists;
    }

    protected function getCacheKey(CacheKeyEnum $cacheKey, string $id): string
    {
        return implode(
            CacheKeyEnum::CACHE_KEY_SEPARATOR, [
                $cacheKey->value,
                $id,
            ]
        );
    }

    /**
     * @param SpotifyWebAPI         $spotifyWebApi
     * @param array<string, string> $trackIds
     *
     * @return array<string, stdClass>
     */
    protected function getAudioFeatures(SpotifyWebAPI $spotifyWebApi, array $trackIds): array
    {
        $cache = Cache::tags([
            ServiceEnum::Spotify->value,
            ServiceEnum::Spotify->value . CacheKeyEnum::CACHE_KEY_SEPARATOR .  CacheKeyEnum::AudioFeature->value
        ]);

        $audioFeatures = [];
        foreach ($trackIds as $index => $trackId) {
            if ($cache->has($trackId)) {
                $audioFeatures[$trackId] = $cache->get($trackId);
                unset($trackIds[$index]);
                logs('crawler')->debug("found audio feature for $trackId in cache");
            }
        }
        if (count($trackIds) > 0) {
            try {
                $response = $spotifyWebApi->getMultipleAudioFeatures(array_values($trackIds));
                foreach ($response->audio_features as $audioFeature) {
                    $cache->put($audioFeature->id, $audioFeature, config('services.spotify.cache_ttl'));
                    $audioFeatures[$audioFeature->id] = $audioFeature;
                    logs('crawler')->debug("set audio feature for $audioFeature->id to cache");
                }
            } catch (Exception $e) {
                logs('crawler')->warning('error while fetching audio feature, skipping. Exception: ' . $e->getMessage());
            }
        }

        return $audioFeatures;
    }

    /**
     * @throws Exception
     */
    protected function writeTrackHistoryPoint(?stdClass $audioFeature, stdClass $track, string $username): void
    {
        $point = $this->factory->getTrackHistoryPoint(
            $username, ServiceEnum::Spotify->value, $audioFeature, $track
        );
        $this->writeApi->write($point);
    }

    /**
     * @throws Exception
     */
    protected function writeGenreHistoryPoints(stdClass $recentTrack, array $artistsById, string $username): void
    {
        $genres = [];
        foreach ($recentTrack->track->artists as $artist) {
            foreach ($artistsById[$artist->id]->genres as $genre) {
                $genres[$genre] = $genre;
            }
        }

        foreach ($genres as $genre) {
            $point = $this->factory->getGenreHistoryPoint($username, ServiceEnum::Spotify->value, $genre, $recentTrack);
            $this->writeApi->write($point);
        }
    }
}
