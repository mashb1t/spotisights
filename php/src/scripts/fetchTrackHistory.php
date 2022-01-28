<?php

use App\Factory;

require __DIR__ . '/../vendor/autoload.php';

echo "starting fetchTrackHistory\n";

// todo make sure session stays active OR (better) use this in async cronjob to run for all file sessions
$username = 'mash1t';

$factory = new Factory();
$sessionHandler = $factory->getSessionHandler();

// todo check if session exists
$session = $sessionHandler->loadSession($username);

$trackHistoryCrawler = $factory->getTrackHistoryCrawler($session);

try {
    echo "starting TrackHistoryCrawler\n";
    $trackHistoryCrawler->crawl($username);
} catch (Exception $exception) {
    echo $exception->getMessage() . "\n";
}


$session = $sessionHandler->saveSession($session, $username);

echo "done\n";
