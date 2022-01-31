<?php

namespace App\Session;

use JetBrains\PhpStorm\Pure;
use SpotifyWebAPI\Session;

class SpotifySession implements SessionInterface
{
    public function __construct(
        protected Session $session
    ) {
    }

    public function getUnderlyingObject(): Session
    {
        return $this->session;
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
}
