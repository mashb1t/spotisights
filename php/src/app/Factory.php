<?php

namespace App;

use App\Crawler\CrawlerInterface;
use App\Crawler\SpotifyCrawler;
use App\Enums\ServiceEnum;
use App\Session\SessionHandler;
use App\Session\SessionInterface;
use App\Session\SpotifySession;
use DateTime;
use Exception;
use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use InfluxDB2\WriteApi;
use InfluxDB2\WriteType;
use JetBrains\PhpStorm\Pure;
use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class Factory
{
    // must be less than or equal to 50 to prevent spotify api errors
    const BATCH_SIZE = 50;

    /**
     * @throws Exception
     */
    public function getSession(string $serviceName): SessionInterface
    {
        return match ($serviceName) {
            ServiceEnum::SPOTIFY->value => new SpotifySession(
                new Session(
                    getenv('SPOTIFY_CLIENT_ID'),
                    getenv('SPOTIFY_CLIENT_SECRET'),
                    getenv('SPOTIFY_REDIRECT_URL'),
                )
            ),
            default => throw new Exception("Session could not be created for service $serviceName"),
        };

    }

    public function getSpotifySession(): SessionInterface
    {
        return new SpotifySession(
            new Session(
                getenv('SPOTIFY_CLIENT_ID'),
                getenv('SPOTIFY_CLIENT_SECRET'),
                getenv('SPOTIFY_REDIRECT_URL'),
            )
        );
    }

    public function getSpotifyWebAPI(Session $session): SpotifyWebAPI
    {
        $options = [
            'auto_refresh' => true,
        ];

        return new SpotifyWebAPI($options, $session);
    }

    /**
     * @param mixed $username
     * @param mixed $audioFeature
     * @param mixed $recentTrack
     *
     * @return Point
     * @throws Exception
     */
    public function getTrackHistoryPoint(
        mixed $username,
        mixed $audioFeature,
        mixed $recentTrack
    ): Point {
        $artists = [];
        foreach ($recentTrack->track->artists as $artist) {
            $artists[] = $artist->name;
        }
        $artists = implode(', ', $artists);

        return Point::measurement('track_history')
            ->addTag('user', $username)
            ->addTag('artists', $artists)
            ->addField('track', $recentTrack->track->name)
            ->addField('duration_ms', (int)$recentTrack->track->duration_ms)
            ->addField('danceability', (float)$audioFeature->danceability)
            ->addField('energy', (float)$audioFeature->energy)
            ->addField('key', (int)$audioFeature->key)
            ->addField('speechiness', (float)$audioFeature->speechiness)
            ->addField('acousticness', (float)$audioFeature->acousticness)
            ->addField('instrumentalness', (float)$audioFeature->instrumentalness)
            ->addField('liveness', (float)$audioFeature->liveness)
            ->addField('valence', (float)$audioFeature->valence)
            ->addField('tempo', round((float)$audioFeature->tempo))
            ->time((new DateTime($recentTrack->played_at)));
    }

    /**
     * @return CrawlerInterface[]
     */
    public function getActiveCrawlers(): array
    {
        $crawlers = [
            ServiceEnum::SPOTIFY->value => $this->getSpotifyCrawler(),
        ];

        $activeCrawlers = [];
        foreach (explode(',', getenv('ACTIVE_SERVICES')) as $activeService) {
            if (isset($crawlers[$activeService])) {
                $activeCrawlers[$activeService] = $crawlers[$activeService];
            }
        }

        return $activeCrawlers;
    }

    protected function getSpotifyCrawler(): SpotifyCrawler
    {
        $writeApi = $this->getInfluxDBWriteApi();
        $sessionHandler = $this->getSessionHandler();

        return new SpotifyCrawler($writeApi, $sessionHandler, $this);
    }

    /**
     * @return WriteApi
     */
    public function getInfluxDBWriteApi(): WriteApi
    {
        $client = new Client([
            'url' => getenv('INFLUXDB_URL'),
            'token' => getenv('INFLUXDB_TOKEN'),
            'bucket' => getenv('INFLUXDB_BUCKET'),
            'org' => getenv('INFLUXDB_ORG'),
            'precision' => WritePrecision::NS,
        ]);

        return $client->createWriteApi([
            'writeType' => WriteType::BATCHING,
            'batchSize' => static::BATCH_SIZE,
        ]);
    }

    #[Pure] public function getSessionHandler(): SessionHandler
    {
        return new SessionHandler($this);
    }
}
