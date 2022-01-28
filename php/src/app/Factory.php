<?php

namespace App;

use App\Crawler\TrackHistoryCrawler;
use App\Session\SessionHandler;
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

    public function getSession(): Session
    {
        return new Session(
            getenv('SPOTIFY_CLIENT_ID'),
            getenv('SPOTIFY_CLIENT_SECRET'),
            getenv('SPOTIFY_REDIRECT_URL'),
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
            ->addField('song', $recentTrack->track->name)
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

    public function getTrackHistoryCrawler(Session $session): TrackHistoryCrawler
    {
        $writeApi = $this->getInfluxDBWriteApi();
        $spotifyWebApi = $this->getSpotifyWebAPI($session);

        return new TrackHistoryCrawler($writeApi, $spotifyWebApi, $this);
    }

    #[Pure] public function getSessionHandler(): SessionHandler
    {
        return new SessionHandler($this);
    }
}
