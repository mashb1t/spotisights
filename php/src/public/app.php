<?php

use App\Factory;
use App\SessionHandler;

require '../vendor/autoload.php';

session_start();

// todo make sure session stays active OR (better) use this in async cronjob to run for all file sessions
$username = $_SESSION['username'];

$client = new InfluxDB2\Client([
    'url' => getenv('INFLUXDB_URL'),
    'token' => getenv('INFLUXDB_TOKEN'),
    'bucket' => getenv('INFLUXDB_BUCKET'),
    'org' => getenv('INFLUXDB_ORG'),
    'precision' => InfluxDB2\Model\WritePrecision::NS,
]);
$writeApi = $client->createWriteApi();

$session = SessionHandler::loadSession($username);

$api = Factory::getSpotifyWebAPI($session);

$recentTracks = $api->getMyRecentTracks(['limit' => 50])->items;

// todo add cache for track audio features (redis?)
// todo prepare data for heatmap of liveability bpm etc.
// todo add counter for song amount

$trackIds = [];
foreach ($recentTracks as $recentTrack) {
    $trackIds[] = $recentTrack->track->id;
}

//https://developer.spotify.com/console/get-audio-features-several-tracks/
$audioFeatures = $api->getMultipleAudioFeatures($trackIds);

foreach ($recentTracks as $index => $recentTrack) {
    $track = $recentTrack->track;

    $artists = [];
    foreach ($track->artists as $artist) {
        $artists[] = $artist->name;
    }
    $artists = implode(', ', $artists);

    try {
        // todo cache audio features for a track (e.g. redis for a month)
        // order if $audioFeatures matches order of $recentTrack
        $audioFeature = $audioFeatures->audio_features[$index];

        $point = InfluxDB2\Point::measurement('track_history')
            ->addTag('user', $username)
            ->addTag('artists', $artists)
            ->addTag('song', $track->name)
            ->addField('duration_ms', (int)$track->duration_ms)
            ->addField('danceability', (float)$audioFeature->danceability)
            ->addField('energy', (float)$audioFeature->energy)
            ->addField('key', (int)$audioFeature->key)
//            ->addField('loudness', (float)$audioFeature->loudness)
//            ->addField('mode', (float)$audioFeature->mode)
            ->addField('speechiness', (float)$audioFeature->speechiness)
            ->addField('acousticness', (float)$audioFeature->acousticness)
            ->addField('instrumentalness', (float)$audioFeature->instrumentalness)
            ->addField('liveness', (float)$audioFeature->liveness)
            ->addField('valence', (float)$audioFeature->valence)
            ->addField('tempo', round((float)$audioFeature->tempo))
//            ->addField('type', (int)$audioFeature->type)
            ->time((new DateTime($recentTrack->played_at)));
        $writeApi->write($point);
    } catch (Exception $exception) {
        echo $exception->getMessage() . "\n\n";
        continue;
    }
}

$writeApi->close();

SessionHandler::saveSession($session, $username);

echo 'done 1';
