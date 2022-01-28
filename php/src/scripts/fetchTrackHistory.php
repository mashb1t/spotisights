<?php

use App\Factory;
use App\Session\SessionHandler;

require __DIR__ . '/../vendor/autoload.php';

function printSpacer() {
    printLn('—---------------------------------------------------------------');
}

function printLn(?string $line = '') {
    echo "$line\n";
}

printLn('starting fetchTrackHistory');

$files = glob(SessionHandler::BASE_FILEPATH  . '/*' . SessionHandler::SESSION_FILE_SUFFIX);
$fileCount = count($files);

printLn("found $fileCount user(s)");

printSpacer();

if ($fileCount === 0) {
    printLn('done');
    exit;
}

foreach ($files as $filename) {

    $username = basename($filename, SessionHandler::SESSION_FILE_SUFFIX);

    println("user $username");

    $factory = new Factory();
    $sessionHandler = $factory->getSessionHandler();

    $session = $sessionHandler->loadSession($username);

    $trackHistoryCrawler = $factory->getTrackHistoryCrawler($session);

    try {
        println('TrackHistoryCrawler start');
        $trackHistoryCrawler->crawl($username);
        println('TrackHistoryCrawler end');
    } catch (Exception $exception) {
        println($exception->getMessage());
    }

    $session = $sessionHandler->saveSession($session, $username);

    println("user $username crawled successfully");

    printSpacer();
}

printLn('done');
