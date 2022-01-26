<?php

require '../vendor/autoload.php';

$session = \App\SessionHandler::getSession();

$state = $session->generateState();
$options = [
    'scope' => [
        'user-read-recently-played',
    ],
    'state' => $state,
];

\App\SessionHandler::saveSession($session, getenv('USERNAME'));

header('Location: ' . $session->getAuthorizeUrl($options));
die();


