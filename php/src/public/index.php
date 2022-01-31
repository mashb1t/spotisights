<?php

// currently exclusively authorizes spotify

use App\Factory;

require __DIR__ . '/../vendor/autoload.php';

session_start();

$factory = new Factory();

$spotifySession = $factory->getSpotifySession();
$session = $spotifySession->getUnderlyingObject();

// only generate state once
$_SESSION['state'] = $_SESSION['state'] ?? $session->generateState();

$options = [
    'scope' => [
        'user-read-recently-played', // used for statistic collection in getMyRecentTracks()
        'user-read-private', // used for reading username in me()
        'user-read-email',  // currently not used, but required for me()
    ],
    'state' => $_SESSION['state'],
];

header('Location: ' . $session->getAuthorizeUrl($options));
die();


