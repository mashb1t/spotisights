<?php

use App\Crawler\CrawlerResultEnum;
use App\Crawler\SpotifyCrawler;
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
$dashboardUrl = getenv('GRAFANA_DASHBOARD_URL');

$crawlers = $factory->getActiveCrawlers();

$crawlerInitialSetup = [];
$crawlerResult = [];

// TODO split up into multiple callback urls per service
foreach ($crawlers as $crawler) {
    $username = $_SESSION[$crawler->getType() . '_username'] ?? null;

    $initialSetupResult = $crawler->initialSetup($username, ['code' => $code]);
    $crawlerInitialSetup[$crawler->getType()] = $initialSetupResult;

    if ($initialSetupResult == CrawlerResultEnum::SESSION_ACCESS_TOKEN_ERROR) {
        header('refresh:5;url=index.php');
        die('Access token could not be created, redirecting to login...');
    } else if ($crawlerInitialSetup == CrawlerResultEnum::SESSION_SETUP_SUCCESS) {
        try {
            // read new username from session if now set by initial setup
            $crawler->crawlAll($_SESSION[$crawler->getType() . '_username']);
            $crawlerResult[$crawler->getType()] = true;
        } catch (Exception $e) {
            $crawlerResult[$crawler::class] = CrawlerResultEnum::CRAWL_FAILED;
        }
    }
}

// TODO use unspecific code here
// check if every initial setup went fine
if ($crawlerInitialSetup[SpotifyCrawler::TYPE] == CrawlerResultEnum::SESSION_SETUP_SUCCESS) {
    // todo idea: create new grafana user via API https://grafana.com/docs/grafana/latest/http_api/admin/#global-users and display password once?

    header("refresh:5;url=$dashboardUrl");
    die('All set up for user ' . $_SESSION['spotify_username'] . ', redirecting to dashboard...');
}

if (!$dashboardUrl) {
    die('All set up for user ' . $_SESSION['spotify_username'] . ', let the cronjob do the rest!');
}

header("refresh:5;url=$dashboardUrl");
die('All set up for user ' . $_SESSION['spotify_username'] . ', let the cronjob do the rest! Redirecting to dashboard...');
