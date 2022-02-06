<?php

use App\Factory;
use App\Session\SessionHandler;

require __DIR__ . '/../vendor/autoload.php';

function printSpacer()
{
    printLine('—---------------------------------------------------------------');
}

function printLine(?string $line = '')
{
    echo "$line\n";
}

$factory = new Factory();
$crawlers = $factory->getActiveCrawlers();
$crawlerCount = count($crawlers);

printLine("found $crawlerCount active service(s): " . implode(', ', array_keys($crawlers)));

printSpacer();

if ($crawlerCount === 0) {
    printLine('done');
    exit;
}

foreach ($crawlers as $service => $crawler) {
    $sessionFiles = glob(
        SessionHandler::BASE_FILEPATH . DIRECTORY_SEPARATOR . $service . DIRECTORY_SEPARATOR . '*' . SessionHandler::SESSION_FILE_SUFFIX
    );
    $sessionFileCount = count($sessionFiles);

    printLine("found $sessionFileCount $service session(s)");

    foreach ($sessionFiles as $index => $sessionFile) {
        $username = basename($sessionFile, SessionHandler::SESSION_FILE_SUFFIX);

        // show index +1 in outputs
        $index++;

        try {
            printLine("$index/$sessionFileCount: crawling user \"$username\"");
            $crawler->crawlAll($username);

        } catch (Exception $exception) {
            printLine("$index/$sessionFileCount: exception while crawling $username, message: " . $exception->getMessage());
        }
    }

    printLine('done');

    printSpacer();
}

printLine('done');
