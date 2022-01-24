<?php

declare(strict_types=1);
require __DIR__ . '/vendor/autoload.php';
require "./functions.php";

$client = new InfluxDB2\Client([
    "url" => "http://localhost:8086",
    "token" => "admin",
    "bucket" => "influx",
    "org" => "my-org",
    "precision" => InfluxDB2\Model\WritePrecision::MS,
]);
$writeApi = $client->createWriteApi();

$accessToken = getAccessToken();
$api = new SpotifyWebAPI\SpotifyWebAPI();
$api->setAccessToken($accessToken);
$recentTracks = $api->getMyRecentTracks(['limit' => 10])->items;

foreach ($recentTracks as $recentTrack) {
    $track = $recentTrack->track;

    $artists = [];
    foreach ($track->artists as $artist) {
        $artists[] = $artist->name;
    }
    $artists = implode(', ', $artists);

    try {
        $point = InfluxDB2\Point::measurement('spotify')
            ->addField('artists',$artists)
            ->addField('song',$track->name)
            ->addField('duration_ms', (float)$track->duration_ms);
        // TODO fix timestamp logging, currently not working (v1 upgrade to v2?)
//            ->time((new DateTime($recentTrack->played_at))->getTimestamp());
        var_dump($point->toLineProtocol());
        $writeApi->write($point);
    } catch (Exception $exception) {
        echo $exception->getMessage() . "\n\n";
        continue;
    }

}

echo 'done';
