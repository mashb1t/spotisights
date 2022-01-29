# SpotiSights - Spotify Insights

This project displays (soon to be sexy) Spotify statistics for multiple users via Grafana.

It uses PHP Scripts for data collection from the Spotify API and InfluxDB as data storage.


## Setup

1. Copy ``config/*.env.dist`` to ``config/*.env`` and change the credentials
2. Run ``composer install`` in ``php/src`` to make sure vendor exists for volume mount
3. Run ``docker-compose up -d`` to provision Grafana, InfluxDB and PHP (via Nginx)
4. Log in to the [Spotify Developer] website
5. Set up a new Spotify App, add the callback URL ``http://localhost:8080/callback.php`` and add client id and client secret to ``php.env``


## Data Flow

1. Call http://localhost:8080/ and follow the displayed auth flow (technical reference: [Authorization Code Flow])
2. app.php is called and collects recent tracks fron the authorized user, saves them to InfluxDB and prompts "done" when finished
3. Open Grafana at http://localhost:3000/ and navigate to the Dashboard "spotisights" (http://localhost:3000/?orgId=1&search=open)


## Auth

This app implements the [Authorization Code Flow] of Spotify with [Refresh Tokens] by using file sessions.

### API Scopes

| scope                     | reason                           | api endpoint         |
|---------------------------|----------------------------------|----------------------|
| user-read-recently-played | used for statistic collection    | getMyRecentTracks()  |
| user-read-private         | used for reading username        | me()                 |
| user-read-email           | currently not used, but required | me()                 |


## Disclaimer ARM CPUs

While there are alternatives for each image and building / running them on linux/amd64 will work with minor adjustments
building and running them on ARM is problematic. 

In any case the image ``mendhak/arm32v6-influxdb`` isn't a 1:1 replacement and you have to manually create the database
after startup or write a script for that.

You also have to start crond manually due to using the user "nobody" during runtime:

    crond -b -L /docker.stdout

### linux/arm/v6

Grafana is only compatible with linux/armv7, the container will not start.

### linux/arm/v7

Despite Grafana now working the php container will not start due to one of the following errors:

```
dns for composer resolution will fail, even when adding the dns server directly to the docker daemon config
```

```
php_1       | Fatal Python error: init_interp_main: can't initialize time
php_1       | Python runtime state: core initialized
php_1       | PermissionError: [Errno 1] Operation not permitted
php_1       |
php_1       | Current thread 0x76f87390 (most recent call first):
php_1       | <no Python frame>
```

This most likely has to do with libseccomp2 not being compatible.

I stopped debugging and used a system with a linux/amd64 CPU.


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
