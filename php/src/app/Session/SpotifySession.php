<?php

namespace App\Session;

use App\Enums\ServiceEnum;
use JetBrains\PhpStorm\Pure;
use SpotifyWebAPI\Session;

class SpotifySession implements SessionInterface
{
    public function __construct(
        protected Session $session
    ) {
    }

    #[Pure] public function getAccessToken(): string
    {
        return $this->session->getAccessToken();
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->session->setAccessToken($accessToken);
    }

    #[Pure] public function getRefreshToken(): string
    {
        return $this->session->getRefreshToken();
    }

    public function setRefreshToken(string $refreshToken): void
    {
        $this->session->setRefreshToken($refreshToken);
    }

    public function getLoginUrl(): string
    {
        $session = $this->getUnderlyingObject();

        $_SESSION['state'] = $_SESSION['state'] ?? $session->generateState();

        $options = [
            'scope' => [
                'user-read-recently-played', // used for statistic collection in getMyRecentTracks()
                'user-read-private', // used for reading username in me()
                'user-read-email',  // currently not used, but required for me()
            ],
            'state' => $_SESSION['state'],
        ];

        return $session->getAuthorizeUrl($options);
    }

    public function getUnderlyingObject(): Session
    {
        return $this->session;
    }

    public function getType(): string
    {
        return ServiceEnum::SPOTIFY->value;
    }
}
