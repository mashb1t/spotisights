<?php

use App\Factory;
use SpotifyWebAPI\SpotifyWebAPIException;

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

$session = $factory->getSession();
$spotifyWebAPI = $factory->getSpotifyWebAPI($session);
$sessionHandler = $factory->getSessionHandler();

$accessTokenCreated = false;
try {
    $accessTokenCreated = $session->requestAccessToken($code);
} catch (SpotifyWebAPIException $exception) {
} finally {
    if (!$accessTokenCreated) {
        header('refresh:5;url=index.php');
        die('Access token could not be created, redirecting to login...');
    }
}

// set refreshToken to redirect directly from index to app without redirect to spotify
$_SESSION['refreshToken'] = $session->getRefreshToken();

// todo add exception handling
$_SESSION['username'] = $spotifyWebAPI->me()->id;

$dashboardUrl = getenv('GRAFANA_DASHBOARD_URL');

if (!$sessionHandler->sessionExists($_SESSION['username'])) {
    $sessionHandler->saveSession($session, $_SESSION['username']);

    $trackHistoryCrawler = $factory->getTrackHistoryCrawler($session);
    $trackHistoryCrawler->crawl($_SESSION['username']);

    // todo idea: create new grafana user via API https://grafana.com/docs/grafana/latest/http_api/admin/#global-users and display password once?

    header("refresh:5;url=$dashboardUrl");
    die('All set up for user ' . $_SESSION['username'] . ', redirecting to dashboard...');
}

if (!$dashboardUrl) {
    die('All set up for user ' . $_SESSION['username'] . ', let the cronjob do the rest!');
}

header("refresh:5;url=$dashboardUrl");
die('All set up for user ' . $_SESSION['username'] . ', let the cronjob do the rest! Redirecting to dashboard...');
