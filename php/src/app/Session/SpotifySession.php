<?php

namespace App\Session;

use App\Enums\ServiceEnum;
use JetBrains\PhpStorm\Pure;
use SpotifyWebAPI\Session as SpotifyWebAPISession;

class SpotifySession implements SessionInterface
{
    public function __construct(
        protected SpotifyWebAPISession $session
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

        if (!session('state')) {
            session(['state' => $session->generateState()]);
        }

        $options = [
            'scope' => [
                'user-read-recently-played', // used for statistic collection in getMyRecentTracks()
                'user-read-private', // used for reading username in me()
                'user-read-email',  // currently not used, but required for me()
            ],
            'state' => session('state'),
        ];

        return $session->getAuthorizeUrl($options);
    }

    public function getUnderlyingObject(): SpotifyWebAPISession
    {
        return $this->session;
    }

    public function getType(): string
    {
        return ServiceEnum::SPOTIFY->value;
    }
}
