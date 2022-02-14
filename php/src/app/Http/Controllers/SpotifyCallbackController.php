<?php

namespace App\Http\Controllers;

use App\Enums\CrawlerResultEnum;
use App\Enums\ServiceEnum;
use App\Factory;
use Exception;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class SpotifyCallbackController extends Controller
{
    public function __construct(
        protected Factory $factory
    ) {
    }

    public function callback(): RedirectResponse
    {
        $state = request('state');
        $sessionState = session('state');

        $code = request('code');

        // check state integrity
        if (!$state || $state !== $sessionState || !$code) {
            abort(Response::HTTP_FORBIDDEN, 'State mismatch or not authenticated');
        }

        $crawlers = $this->factory->getActiveCrawlers();

        $serviceNameSpotify = ServiceEnum::Spotify->value;
        if (!isset($crawlers[$serviceNameSpotify])) {
            abort(Response::HTTP_BAD_REQUEST, "Service $serviceNameSpotify is not active!");
        }

        $crawler = $crawlers[$serviceNameSpotify];

        $username = session($crawler->getType() . '_username');

        // do initial crawl
        $initialSetupResult = $crawler->initialSetup($username, ['code' => $code]);

        if ($initialSetupResult === CrawlerResultEnum::SESSION_ACCESS_TOKEN_ERROR) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, CrawlerResultEnum::SESSION_ACCESS_TOKEN_ERROR->value);
        } else if ($initialSetupResult === CrawlerResultEnum::SESSION_SETUP_SUCCESS) {
            try {
                // read new username from session if now set by initial setup
                $crawler->crawlAll(session($crawler->getType() . '_username'));
            } catch (Exception $exception) {
                abort(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getMessage());
            }
        }

        session(['logged_in_' . $crawler->getType() => true]);

        return redirect()->route('connect');
    }
}
