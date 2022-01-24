<?php

function getAccessToken(): string
{
    // TODO implement personalized auth flow with scope(s)

    $session = new SpotifyWebAPI\Session(
        'foo',
        'bar'
    );

    $session->requestCredentialsToken();

    return $session->getAccessToken();
}
