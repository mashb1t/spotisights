<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$state = $_GET['state'];

// Fetch the stored state value from somewhere. A session for example

$username = $_ENV['USERNAME'];
$session = \App\SessionHandler::loadSession($username);
//$storedState = \App\SessionHandler::getStateFromSession($username);

// TODO add state check
//if ($state !== $storedState) {
//    // The state returned isn't the same as the one we've stored, we shouldn't continue
//    die('State mismatch');
//}

// Request a access token using the code from Spotify
$session->requestAccessToken($_GET['code']);
$session = \App\SessionHandler::saveSession($session, $username);

// Send the user along and fetch some data!
header('Location: app.php');
die();
