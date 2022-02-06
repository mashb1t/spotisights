<?php

use App\Enums\ServiceEnum;
use InfluxDB2\Client;
use InfluxDB2\Model\WritePrecision;
use InfluxDB2\Point;
use InfluxDB2\WriteType;

require __DIR__ . '/../vendor/autoload.php';

function printSpacer()
{
    printLine('—---------------------------------------------------------------');
}

function printLine(?string $line = '')
{
    echo "$line\n";
}

$clientV1 = new InfluxDB\Client(host: 'influxdb', username: 'admin', password: 'admin');

$databaseV1 = $clientV1->selectDB('influx');

$query = $databaseV1->getQueryBuilder()
    ->select('*')
    ->from('track_history')
    ->where(["service = ''"])
    ->getQuery();

$result = $databaseV1->query($query);
$points = $result->getPoints();

$clientV2 = new Client([
    'url' => getenv('INFLUXDB_URL'),
    'token' => getenv('INFLUXDB_TOKEN'),
    'bucket' => getenv('INFLUXDB_BUCKET'),
    'org' => getenv('INFLUXDB_ORG'),
    'precision' => WritePrecision::NS,
]);

$writeApiV2 = $clientV2->createWriteApi([
    'writeType' => WriteType::BATCHING,
    'batchSize' => 50,
]);

foreach ($points as $point) {
    $point = Point::measurement('track_history')
        ->addTag('user', $point['user'])
        ->addTag('artists', $point['artists'])
        ->addTag('service', ServiceEnum::SPOTIFY->value)
        ->addField('track', $point['track'])
        ->addField('duration_ms', (int)$point['duration_ms'])
        ->addField('danceability', (float)$point['danceability'])
        ->addField('energy', (float)$point['energy'])
        ->addField('key', (int)$point['key'])
        ->addField('speechiness', (float)$point['speechiness'])
        ->addField('acousticness', (float)$point['acousticness'])
        ->addField('instrumentalness', (float)$point['instrumentalness'])
        ->addField('liveness', (float)$point['liveness'])
        ->addField('valence', (float)$point['valence'])
        ->addField('tempo', (float)$point['tempo'])
        ->time(new DateTime($point['time']));

    $writeApiV2->write($point);
}

$writeApiV2->close();

$databaseV1->query("DELETE FROM \"track_history\" WHERE \"service\" = ''");

printLine('done');
