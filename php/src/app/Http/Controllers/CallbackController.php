<?php

namespace App\Http\Controllers;

use App\Enums\CrawlerResultEnum;
use App\Enums\ServiceEnum;
use App\Factory;
use Exception;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class CallbackController extends Controller
{
    public function __construct(
        protected Factory $factory
    ) {
    }

    public function spotifyCallback(): RedirectResponse
    {
        $state = request('state');
        $sessionState = session('state');

        $code = request('code');

        // check state integrity
        if (!$state || $state !== $sessionState || !$code) {
            abort(Response::HTTP_FORBIDDEN, 'State mismatch or not authenticated');
        }

        $this->initializeAndCrawl(ServiceEnum::Spotify->value, ['code' => $code]);

        return redirect()->route('connect');
    }

    protected function initializeAndCrawl(string $service, array $parameters): void
    {
        $crawlers = $this->factory->getActiveCrawlers();

        if (!isset($crawlers[$service])) {
            abort(Response::HTTP_BAD_REQUEST, "Service $service is not active!");
        }

        $crawler = $crawlers[$service];

        $username = session($crawler->getType() . '_username');

        // do initial crawl
        $initialSetupResult = $crawler->initialSetup($username, $parameters);

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
    }
}
