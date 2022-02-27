<?php

namespace App;

use App\Crawler\CrawlerInterface;
use App\Crawler\SpotifyCrawler;
use App\Enums\ServiceEnum;
use App\Services\MigrateInfluxDataService;
use App\Session\SessionHandler;
use App\Session\SessionInterface;
use App\Session\SpotifySession;
use Carbon\Carbon;
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
use stdClass;

class Factory
{
    /**
     * @throws Exception
     */
    public function getSession(string $serviceName): SessionInterface
    {
        return match ($serviceName) {
            ServiceEnum::Spotify->value => new SpotifySession(
                $this->getSpotifyWebApiSession()
            ),
            default => throw new Exception("Session could not be created for service $serviceName"),
        };

    }

    public function getSpotifySession(): SessionInterface
    {
        return new SpotifySession(
            $this->getSpotifyWebApiSession()
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
     * @throws Exception
     */
    public function getTrackHistoryPoint(
        string $username,
        string $service,
        stdClass $audioFeature,
        stdClass $track
    ): Point {
        $artists = [];
        foreach ($track->track->artists as $artist) {
            $artists[] = $artist->name;
        }
        $artistsImploded = implode(', ', $artists);

        $playedAtDateTime = new Carbon($track->played_at);
        // use UTC+0 for hour
        $hourOfDay = $playedAtDateTime->hour;

        return Point::measurement('track_history')
            ->addTag('user', $username)
            ->addTag('artists', $artistsImploded)
            ->addTag('service', $service)
            ->addTag('hour_of_day', (string)$hourOfDay)
            ->addField('track', $track->track->name)
            ->addField('duration_ms', (int)$track->track->duration_ms)
            ->addField('danceability', (float)$audioFeature->danceability)
            ->addField('energy', (float)$audioFeature->energy)
            ->addField('key', (int)$audioFeature->key)
            ->addField('speechiness', (float)$audioFeature->speechiness)
            ->addField('acousticness', (float)$audioFeature->acousticness)
            ->addField('instrumentalness', (float)$audioFeature->instrumentalness)
            ->addField('liveness', (float)$audioFeature->liveness)
            ->addField('valence', (float)$audioFeature->valence)
            ->addField('tempo', round((float)$audioFeature->tempo))
            ->time($playedAtDateTime);
    }

    /**
     * @throws Exception
     */
    public function getGenreHistoryPoint(
        string $username,
        string $service,
        mixed $genre,
        mixed $recentTrack
    ): Point {
        return Point::measurement('genre_history')
            ->addTag('user', $username)
            ->addTag('service', $service)
            ->addTag('genre', $genre)
            ->addField('value', 1)
            ->time((new DateTime($recentTrack->played_at)));
    }

    /**
     * @return CrawlerInterface[]
     */
    public function getActiveCrawlers(): array
    {
        $crawlers = [
            ServiceEnum::Spotify->value => $this->getSpotifyCrawler(),
        ];

        $activeCrawlers = [];
        foreach (config('spotisights.services.active') as $activeService) {
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
            'url' => config('database.connections.influx.url'),
            'token' => config('database.connections.influx.token'),
            'bucket' => config('database.connections.influx.bucket'),
            'org' => config('database.connections.influx.org'),
            'precision' => config('database.connections.influx.precision'),
        ]);

        return $client->createWriteApi([
            'writeType' => WriteType::BATCHING,
            'batchSize' => config('database.connections.influx.batch_size'),
        ]);
    }

    #[Pure] public function getSessionHandler(): SessionHandler
    {
        return new SessionHandler($this);
    }

    /**
     * @return Session
     */
    protected function getSpotifyWebApiSession(): Session
    {
        return new Session(
            config('services.spotify.client_id'),
            config('services.spotify.client_secret'),
            config('services.spotify.redirect_url'),
        );
    }

    #[Pure] public function getMigrateInfluxDataService(): MigrateInfluxDataService
    {
        return new MigrateInfluxDataService();
    }
}
