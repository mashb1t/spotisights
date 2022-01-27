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

foreach ($recentTracks as $recentTrack) {
    $track = $recentTrack->track;

    $artists = [];
    foreach ($track->artists as $artist) {
        $artists[] = $artist->name;
    }
    $artists = implode(', ', $artists);

    try {
        // todo cache audio features for a track (e.g. redis for a month)
        $songInfos = $api->getAudioFeatures($track->id);

        $point = InfluxDB2\Point::measurement('track_history')
            ->addTag('user', $username)
            ->addTag('artists', $artists)
            ->addTag('song', $track->name)
            ->addField('duration_ms', (int)$track->duration_ms)
            ->addField('danceability', (float)$songInfos->danceability)
            ->addField('energy', (float)$songInfos->energy)
            ->addField('key', (int)$songInfos->key)
//            ->addField('loudness', (float)$songInfos->loudness)
//            ->addField('mode', (float)$songInfos->mode)
            ->addField('speechiness', (float)$songInfos->speechiness)
            ->addField('acousticness', (float)$songInfos->acousticness)
            ->addField('instrumentalness', (float)$songInfos->instrumentalness)
            ->addField('liveness', (float)$songInfos->liveness)
            ->addField('valence', (float)$songInfos->valence)
            ->addField('tempo', round((float)$songInfos->tempo))
//            ->addField('type', (int)$songInfos->type)
            ->time((new DateTime($recentTrack->played_at)));
        $writeApi->write($point);
    } catch (Exception $exception) {
        echo $exception->getMessage() . "\n\n";
        continue;
    }
}

$writeApi->close();

SessionHandler::saveSession($session, $username);

echo 'done';
