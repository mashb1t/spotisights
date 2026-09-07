<?php

namespace App\Spotify;

use SpotifyWebAPI\Request;

/**
 * Works around a bug in jwilsson/spotify-web-api-php <= 6.x where GET query
 * parameters are appended to the URL with a trailing slash ("/?..."), e.g.
 * "/v1/me/player/recently-played/?limit=50". Spotify now rejects that variant
 * with a 404 (empty body), which surfaces as "An unknown error occurred.".
 *
 * The fix (removing the slash) only landed upstream in 7.x. To avoid the
 * two-major-version upgrade we bake the query string into the URL ourselves and
 * hand the parent an empty parameter set, so the buggy "/?" branch is skipped.
 */
class SpotifyRequest extends Request
{
    public function send($method, $url, $parameters = [], $headers = [])
    {
        if (strtoupper($method) === 'GET' && !empty($parameters)) {
            $query = (is_array($parameters) || is_object($parameters))
                ? http_build_query($parameters, '', '&')
                : $parameters;

            if ($query !== '') {
                $url = rtrim($url, '/') . '?' . $query;
                $parameters = [];
            }
        }

        return parent::send($method, $url, $parameters, $headers);
    }
}
