<?php

use App\SessionHandler as SessionHandlerAlias;

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$username = $_ENV['USERNAME'];

$client = new InfluxDB2\Client([
    'url' => $_ENV['INFLUXDB_URL'],
    'token' => $_ENV['INFLUXDB_TOKEN'],
    'bucket' => $_ENV['INFLUXDB_BUCKET'],
    'org' => $_ENV['INFLUXDB_ORG'],
    'precision' => InfluxDB2\Model\WritePrecision::NS,
]);
$writeApi = $client->createWriteApi();

$session = SessionHandlerAlias::loadSession($username);

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

SessionHandlerAlias::saveSession($session, $username);

echo 'done';
