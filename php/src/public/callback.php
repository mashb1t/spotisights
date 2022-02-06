<?php

use App\Enums\CrawlerResultEnum;
use App\Factory;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$state = $_GET['state'] ?? null;
$sessionState = $_SESSION['state'] ?? null;

$code = $_GET['code'] ?? null;

// check state integrity
if (!$state || $state !== $sessionState || !$code) {
    header('refresh:5;url=index.php');
    die('State mismatch or not authenticated, redirecting to login...');
}

$factory = new Factory();
$crawlers = $factory->getActiveCrawlers();

$crawlerInitialSetup = [];
$crawlerResult = [];

// TODO split up into multiple callback urls per service
foreach ($crawlers as $crawler) {
    $username = $_SESSION[$crawler->getType() . '_username'] ?? null;

    $initialSetupResult = $crawler->initialSetup($username, ['code' => $code]);
    $crawlerInitialSetup[$crawler->getType()] = $initialSetupResult;

    if ($initialSetupResult == CrawlerResultEnum::SESSION_ACCESS_TOKEN_ERROR) {
        $_SESSION['logged_in'][$crawler->getType()] = false;
        header('refresh:5;url=index.php');
        die(CrawlerResultEnum::SESSION_ACCESS_TOKEN_ERROR->value);
    } else if ($crawlerInitialSetup == CrawlerResultEnum::SESSION_SETUP_SUCCESS) {
        try {
            // read new username from session if now set by initial setup
            $crawler->crawlAll($_SESSION[$crawler->getType() . '_username']);
            $crawlerResult[$crawler->getType()] = true;
        } catch (Exception $exception) {
            $crawlerResult[$crawler->getType()] = CrawlerResultEnum::CRAWL_FAILED;
            die($exception->getMessage());
        }
    }

    $_SESSION['logged_in'][$crawler->getType()] = true;
}
header('Location: index.php');
die();
