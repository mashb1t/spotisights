<?php

namespace App\Crawler;

use App\Enums\CrawlerResultEnum;
use App\Enums\ServiceEnum;
use App\Factory;
use App\Session\SessionHandler;
use Exception;
use InfluxDB2\WriteApi;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;
use SpotifyWebAPI\SpotifyWebAPIException;
use stdClass;

class SpotifyCrawler implements CrawlerInterface
{
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

        $this->crawlTrackHistoryAndAudioFeatures($username, $spotifyWebApi);

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

        $trackIds = [];
        $artistIds = [];
        foreach ($recentTracks as $index => $recentTrack) {
            $trackIds[$index] = $recentTrack->track->id;

            foreach ($recentTrack->track->artists as $artist) {
                $artistIds[$artist->id] = $artist->id;
            }
        }

        $artistsById = $this->getArtistsById($artistIds, $spotifyWebApi);

        // TODO cache audio features for a track (e.g. redis for a month or even longer)
        $audioFeatures = $spotifyWebApi->getMultipleAudioFeatures($trackIds);

        foreach ($recentTracks as $index => $recentTrack) {
            // order of $audioFeatures matches order of $recentTrack
            $audioFeature = $audioFeatures->audio_features[$index];

            $point = $this->factory->getTrackHistoryPoint($username, ServiceEnum::Spotify->value, $audioFeature, $recentTrack);
            $this->writeApi->write($point);

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

        $this->writeApi->close();
    }

    /**
     * @param array $artistIds
     * @param SpotifyWebAPI $spotifyWebApi
     *
     * @return stdClass[]
     */
    protected function getArtistsById(array $artistIds, SpotifyWebAPI $spotifyWebApi): array
    {
        // artistIds count could be more than Factory::BATCH_SIZE
        $artistIdsChunks = array_chunk($artistIds, config('services.spotify.crawl_bulk_limit'));

        $artistsFromAPI = [];
        foreach ($artistIdsChunks as $artistIdsChunk) {
            // TODO cache artists (e.g. redis for a month or even longer)
            $response = $spotifyWebApi->getArtists($artistIdsChunk);
            array_push($artistsFromAPI, ...$response->artists);
        }

        $artistsById = [];
        foreach ($artistsFromAPI as $artistFromAPI) {
            $artistsById[$artistFromAPI->id] = $artistFromAPI;
        }

        return $artistsById;
    }
}
