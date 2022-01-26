# SpotiSights - Spotify Insights

This project displays (soon to be sexy) Spotify Insights via Grafana.

It uses a PHP Script for fetching data from the Spotify API and saves it to InfluxDB.

## Setup

It is advised to use a PHP website serving tool of your choice, such as [Laravel Valet].

1. Copy ``configuration.env.dist`` to ``configuration.env`` and change the credentials
1. Copy ``php/.env.dist`` to ``php/env`` and change the credentials
2. Run ``docker-compose up -d`` to provision Grafana and InfluxDB
3. Log in to the [Spotify Developer] website
4. Set up a new Spotify App, save client id and client secret on your local machine
5. Set up static website serving by using ``cd php && valet link spotisights``
6. (optional) Make your connection secure by executing ``valet secure``
7. Edit your Spotify App and add the callback URL ``https://spotisights.test/callback.php`` (http when not securing the connection)

## Data Flow

1. Call https://spotisights.test/ and follow the displayed auth flow (technical reference: [Authorization Code Flow])
2. app.php will be called which collects recent tracks fron the logged in user, saves them to InfluxDB and prompts "done" when finished
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
