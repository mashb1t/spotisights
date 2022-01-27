<?php

use App\Factory;

require '../vendor/autoload.php';

session_start();

if (isset($_SESSION['refreshToken'])) {
    header('Location: app.php');
    die();
}

$session = Factory::getSession();

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


