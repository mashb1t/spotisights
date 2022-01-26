<?php

use App\SessionHandler;

require '../vendor/autoload.php';

$username = getenv('USERNAME');

$client = new InfluxDB2\Client([
    'url' => getenv('INFLUXDB_URL'),
    'token' => getenv('INFLUXDB_TOKEN'),
    'bucket' => getenv('INFLUXDB_BUCKET'),
    'org' => getenv('INFLUXDB_ORG'),
    'precision' => InfluxDB2\Model\WritePrecision::NS,
]);
$writeApi = $client->createWriteApi();

$session = SessionHandler::loadSession($username);

$options = [
    'auto_refresh' => true,
];

$api = new SpotifyWebAPI\SpotifyWebAPI($options, $session);

$recentTracks = $api->getMyRecentTracks(['limit' => 50])->items;

// todo add cache for tracks
// todo add song infos such as genre, bpm, liveliness score etc.

foreach ($recentTracks as $recentTrack) {
    $track = $recentTrack->track;

    $artists = [];
    foreach ($track->artists as $artist) {
        $artists[] = $artist->name;
    }
    $artists = implode(', ', $artists);

    try {
        $point = InfluxDB2\Point::measurement('spotisights')
            ->addTag('user',$username)
            ->addField('artists',$artists)
            ->addField('song',$track->name)
            ->addField('duration_ms', (float)$track->duration_ms)
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
