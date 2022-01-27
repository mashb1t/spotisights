<?php

namespace App;

use SpotifyWebAPI\Session;

class SessionHandler
{
    const BASE_FILEPATH = '..' . DIRECTORY_SEPARATOR . 'sessions';

    public static function saveSession(Session $session, string $username): bool
    {
        $accessToken = $session->getAccessToken();
        $refreshToken = $session->getRefreshToken();

        $content = json_encode([
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ]);

        return (bool)file_put_contents(
            static::BASE_FILEPATH . DIRECTORY_SEPARATOR . $username . '.txt',
            $content
        );
    }

    public static function loadSession(string $username): Session
    {
        $content = file_get_contents(static::BASE_FILEPATH . DIRECTORY_SEPARATOR . $username . '.txt');
        $content = json_decode($content, true);

        $session = Factory::getSession();
        $session->setAccessToken($content['accessToken']);
        $session->setRefreshToken($content['refreshToken']);

        return $session;
    }
}
