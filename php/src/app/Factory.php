<?php

namespace App;

use SpotifyWebAPI\Session;
use SpotifyWebAPI\SpotifyWebAPI;

class Factory
{
    public static function getSession(): Session
    {
        return new Session(
            getenv('SPOTIFY_CLIENT_ID'),
            getenv('SPOTIFY_CLIENT_SECRET'),
            getenv('SPOTIFY_REDIRECT_URL'),
        );
    }

    public static function getSpotifyWebAPI(Session $session): SpotifyWebAPI
    {
        $options = [
            'auto_refresh' => true,
        ];

        return new SpotifyWebAPI($options, $session);
    }
}
