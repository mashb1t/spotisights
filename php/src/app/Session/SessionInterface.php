<?php

namespace App\Session;

interface SessionInterface
{
    public function getUnderlyingObject(): mixed;

    public function getAccessToken(): string;

    public function setAccessToken(string $accessToken): void;

    public function getRefreshToken(): string;

    public function setRefreshToken(string $refreshToken): void;

    public function getLoginUrl(): string;

    public function getType(): string;
}
