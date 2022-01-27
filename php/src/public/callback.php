<?php

use App\Factory;
use App\SessionHandler;

require '../vendor/autoload.php';

session_start();

$state = $_GET['state'];

// check state integrity
if ($state !== $_SESSION['state']) {
    die('State mismatch');
}


$session = Factory::getSession();
$session->requestAccessToken($_GET['code']);

// set refreshToken to redirect directly from index to app without redirect to spotify
$_SESSION['refreshToken'] = $session->getRefreshToken();

// todo add exception handling
$api = Factory::getSpotifyWebAPI($session);
$_SESSION['username'] = $api->me()->id;

SessionHandler::saveSession($session, $_SESSION['username']);
header('Location: app.php');
die();
