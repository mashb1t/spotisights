<?php

namespace App\Session;

use App\Enums\ServiceEnum;
use App\Factory;
use Exception;

class SessionHandler
{
    const BASE_FILEPATH = __DIR__ . '/../../sessions';
    const SESSION_FILE_SUFFIX = '.txt';

    public function __construct(
        protected Factory $factory
    ) {
    }

    public function saveSession(SessionInterface $session, string $username): bool
    {
        $accessToken = $session->getAccessToken();
        $refreshToken = $session->getRefreshToken();

        $content = json_encode([
            'accessToken' => $accessToken,
            'refreshToken' => $refreshToken,
        ]);

        $filepath = $this->getFilepath($session->getType(), $username);
        if (!is_dir(dirname($filepath))) {
            mkdir(dirname($filepath), 0775, true);
        }

        return (bool)file_put_contents($filepath, $content);
    }

    /**
     * @throws Exception
     */
    public function loadSession(ServiceEnum $service, string $username): SessionInterface
    {
        $content = file_get_contents($this->getFilepath($service->value, $username));

        if (!$content) {
            throw new Exception('File not found');
        }

        $content = json_decode($content, true);

        $session = $this->factory->getSpotifySession();
        $session->setAccessToken($content['accessToken']);
        $session->setRefreshToken($content['refreshToken']);

        return $session;
    }

    public function sessionExists(string $username): bool
    {
        return file_exists(static::BASE_FILEPATH . DIRECTORY_SEPARATOR . $username . static::SESSION_FILE_SUFFIX);
    }

    protected function getFilepath(string $service, string $username): string
    {
        return implode(DIRECTORY_SEPARATOR, [
            static::BASE_FILEPATH,
            $service,
            $username . static::SESSION_FILE_SUFFIX,
        ]);
    }
}
