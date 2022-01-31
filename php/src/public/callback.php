<?php

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

foreach ($crawlers as $crawler) {

    $username = $_SESSION[$crawler->getType() . '_username'] ?? null;

    $initialSetupResult = $crawler->initialSetup($username, ['code' => $code]);
    $crawlerInitialSetup[$crawler->getType()] = $initialSetupResult;

    // TODO change condition with enum code "user set up" to not always crawl
    if ($initialSetupResult) {
        try {
            // read new username from session if now set by initial setup
            $crawler->crawlAll($_SESSION[$crawler->getType() . '_username']);
            $crawlerResult[$crawler->getType()] = true;
        } catch (Exception $e) {
            die($e->getMessage());
//            $crawlerResult[$crawler::class] = false;
        }
    }
}

// TODO use unspecific code here
// check if every initial setup went fine
if (!in_array(false, $crawlerInitialSetup, true)) {
    // todo idea: create new grafana user via API https://grafana.com/docs/grafana/latest/http_api/admin/#global-users and display password once?

    header("refresh:5;url=$dashboardUrl");
    die('All set up for user ' . $_SESSION['spotify_username'] . ', redirecting to dashboard...');
}

if (!$dashboardUrl) {
    die('All set up for user ' . $_SESSION['spotify_username'] . ', let the cronjob do the rest!');
}

header("refresh:5;url=$dashboardUrl");
die('All set up for user ' . $_SESSION['spotify_username'] . ', let the cronjob do the rest! Redirecting to dashboard...');
