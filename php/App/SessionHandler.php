<?php

namespace App;

use SpotifyWebAPI\Session;

class SessionHandler
{
    const BASE_FILEPATH = 'sessions';

    public static function getSession(): Session
    {
        return new Session(
            $_ENV['SPOTIFY_CLIENT_ID'],
            $_ENV['SPOTIFY_CLIENT_SECRET'],
            $_ENV['SPOTIFY_REDIRECT_URL'],
        );
    }

    public static function saveSession(Session $session, string $username): bool
    {
        $accessToken = $session->getAccessToken();
        $refreshToken = $session->getRefreshToken();

        $content = json_encode([
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
            'state' => $session->generateState(),
        ]);

        return (bool) file_put_contents(
            static::BASE_FILEPATH . DIRECTORY_SEPARATOR . $username . '.txt',
            $content
        );
    }

    public static function loadSession(string $username): Session
    {
        $content = file_get_contents(static::BASE_FILEPATH . DIRECTORY_SEPARATOR . $username . '.txt');
        $content = json_decode($content, true);

        $session = static::getSession();
        $session->setAccessToken($content['accessToken']);
        $session->setRefreshToken($content['refreshToken']);

        return $session;
    }

    public static function getStateFromSession(string $username): string
    {
        $content = file_get_contents(static::BASE_FILEPATH . DIRECTORY_SEPARATOR . $username . '.txt');
        $content = json_decode($content, true);

        return $content['state'];
    }
}
