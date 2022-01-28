<?php

namespace App\Crawler;

use App\Factory;
use Exception;
use InfluxDB2\WriteApi;
use SpotifyWebAPI\SpotifyWebAPI;

class TrackHistoryCrawler
{
    public function __construct(
        protected WriteApi $writeApi,
        protected SpotifyWebAPI $spotifyWebAPI,
        protected Factory $factory,
    ) {
    }

    /**
     * @throws Exception
     */
    public function crawl(string $username): void
    {
        $recentTracks = $this->spotifyWebAPI->getMyRecentTracks(['limit' => Factory::BATCH_SIZE])->items;

        $trackIds = [];
        foreach ($recentTracks as $recentTrack) {
            $trackIds[] = $recentTrack->track->id;
        }

        // todo cache audio features for a track (e.g. redis for a month)
        $audioFeatures = $this->spotifyWebAPI->getMultipleAudioFeatures($trackIds);

        foreach ($recentTracks as $index => $recentTrack) {
            // order of $audioFeatures matches order of $recentTrack
            $audioFeature = $audioFeatures->audio_features[$index];

            $point = $this->factory->getTrackHistoryPoint($username, $audioFeature, $recentTrack);
            $this->writeApi->write($point);
        }

        $this->writeApi->close();
    }
}
