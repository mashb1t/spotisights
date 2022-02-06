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

        if ($username && $this->sessionHandler->sessionExists($username)) {
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
            $_SESSION[$this->getType() . '_username'] = $username;
        }

        $this->sessionHandler->saveSession($spotifySession, $username);

        return CrawlerResultEnum::SESSION_SETUP_SUCCESS;
    }

    public function getType(): string
    {
        return ServiceEnum::SPOTIFY->value;
    }

    /**
     * @throws Exception
     */
    public function crawlAll(string $username): void
    {
        $spotifySession = $this->sessionHandler->loadSession(ServiceEnum::SPOTIFY, $username);

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
        $recentTracks = $spotifyWebApi->getMyRecentTracks(['limit' => Factory::BATCH_SIZE])->items;

        $trackIds = [];
        foreach ($recentTracks as $recentTrack) {
            $trackIds[] = $recentTrack->track->id;
        }

        // todo cache audio features for a track (e.g. redis for a month)
        $audioFeatures = $spotifyWebApi->getMultipleAudioFeatures($trackIds);

        foreach ($recentTracks as $index => $recentTrack) {
            // order of $audioFeatures matches order of $recentTrack
            $audioFeature = $audioFeatures->audio_features[$index];

            $point = $this->factory->getTrackHistoryPoint($username, $audioFeature, $recentTrack);
            $this->writeApi->write($point);
        }

        $this->writeApi->close();
    }
}
