# SpotiSights - Spotify Insights

This project displays (soon to be sexy) Spotify Insights via Grafana.

It uses a PHP Script for fetching data from the Spotify API and saves it to InfluxDB.

## Setup

It is advised to use a PHP website serving tool of your choice, such as [Laravel Valet].

1. Copy ``config/*.env.dist`` to ``config/*.env`` and change the credentials
2. Run ``docker-compose up -d`` to provision Grafana, InfluxDB and PHP (via Nginx)
3. Log in to the [Spotify Developer] website
4. Set up a new Spotify App, add the callback URL ``http://localhost:8080/callback.php`` and add client id and client secret to ``php.env``

## Data Flow

1. Call http://localhost:8080/ and follow the displayed auth flow (technical reference: [Authorization Code Flow])
2. app.php is called and collects recent tracks fron the authorized user, saves them to InfluxDB and prompts "done" when finished
3. Open Grafana at http://localhost:3000/ and navigate to the Dashboard "spotisights" (http://localhost:3000/?orgId=1&search=open)

## Auth

This app implements the [Authorization Code Flow] of Spotify with [Refresh Tokens] by using file sessions.


## References

### Spotify API
- https://developer.spotify.com/console/get-recently-played/?limit=2&after=&before=
- https://github.com/jwilsson/spotify-web-api-php

### InfluxDB
- https://github.com/influxdata/influxdb-client-php

[Authorization Code Flow]: https://developer.spotify.com/documentation/general/guides/authorization/code-flow/
[Laravel Valet]: https://laravel.com/docs/master/valet
[Refresh Tokens]: https://github.com/jwilsson/spotify-web-api-php/blob/main/docs/examples/refreshing-access-tokens.md
[Spotify Developer]: https://developer.spotify.com/dashboard/
