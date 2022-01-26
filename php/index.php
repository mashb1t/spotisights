<?php

require 'vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$session = \App\SessionHandler::getSession();

$state = $session->generateState();
$options = [
    'scope' => [
        'user-read-recently-played',
    ],
    'state' => $state,
];

\App\SessionHandler::saveSession($session, $_ENV['USERNAME']);

header('Location: ' . $session->getAuthorizeUrl($options));
die();


