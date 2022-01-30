<?php

namespace App\Session;

use App\Factory;
use SpotifyWebAPI\Session;

class SessionHandler
{
    const BASE_FILEPATH = __DIR__ . '/../../sessions';
    const SESSION_FILE_SUFFIX = '.txt';

    public function __construct(
        protected Factory $factory
    ) {}

    public function saveSession(Session $session, string $username): bool
    {
        $accessToken = $session->getAccessToken();
        $refreshToken = $session->getRefreshToken();

        $content = json_encode([
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ]);

        return (bool)file_put_contents(
            static::BASE_FILEPATH . DIRECTORY_SEPARATOR . $username . static::SESSION_FILE_SUFFIX,
            $content
        );
    }

    public function loadSession(string $username): Session
    {
        $content = file_get_contents(static::BASE_FILEPATH . DIRECTORY_SEPARATOR . $username . static::SESSION_FILE_SUFFIX);
        $content = json_decode($content, true);

        $session = $this->factory->getSession();
        $session->setAccessToken($content['accessToken']);
        $session->setRefreshToken($content['refreshToken']);

        return $session;
    }

    /**
     * @return Factory
     */
    public function sessionExists(string $username): bool
    {
        return file_exists(static::BASE_FILEPATH . DIRECTORY_SEPARATOR . $username . static::SESSION_FILE_SUFFIX);
    }
}
