<?php

use App\Factory;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$state = $_GET['state'];

// check state integrity
if ($state !== $_SESSION['state']) {
    die('State mismatch');
}

$factory = new Factory();

$session = $factory->getSession();
$spotifyWebAPI = $factory->getSpotifyWebAPI($session);
$sessionHandler = $factory->getSessionHandler();

$session->requestAccessToken($_GET['code']);

// set refreshToken to redirect directly from index to app without redirect to spotify
$_SESSION['refreshToken'] = $session->getRefreshToken();

// todo add exception handling
$_SESSION['username'] = $spotifyWebAPI->me()->id;

$sessionHandler->saveSession($session, $_SESSION['username']);

echo 'all set up for user ' . $_SESSION['username'] . ', let the cronjob do the rest!';
