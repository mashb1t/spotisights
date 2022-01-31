<?php

use App\Factory;
use App\Session\SessionHandler;

require __DIR__ . '/../vendor/autoload.php';

function printSpacer() {
    printLine('—---------------------------------------------------------------');
}

function printLine(?string $line = '') {
    echo "$line\n";
}

printLine('starting fetchTrackHistory');

$files = glob(SessionHandler::BASE_FILEPATH  . '/*' . SessionHandler::SESSION_FILE_SUFFIX);
$fileCount = count($files);

printLine("found $fileCount user(s)");

printSpacer();

if ($fileCount === 0) {
    printLine('done');
    exit;
}

foreach ($files as $filename) {

    $username = basename($filename, SessionHandler::SESSION_FILE_SUFFIX);

    printLine("user $username");

    $factory = new Factory();

    $crawlers = $factory->getActiveCrawlers();

    foreach ($crawlers as $crawler) {
        try {
            printLine($crawler::class . ' start');
            $crawler->crawlAll($username);
            printLine($crawler::class . ' end');
        } catch (Exception $exception) {
            printLine($exception->getMessage());
        }
    }

    printLine("user $username crawled successfully");
    printSpacer();
}

printLine('done');
