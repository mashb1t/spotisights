<?php

use App\Enums\CrawlerResultEnum;
use App\Enums\ServiceEnum;
use App\Factory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    $factory = new Factory();
    $services = explode(',', env('ACTIVE_SERVICES'));

    $loginUrls = [];
    foreach ($services as $service) {
        $loginUrls[$service] = $factory->getSession($service)->getLoginUrl();
    }

    return view('dashboard', [
        'services' => $services,
        'loginUrls' => $loginUrls,
    ]);
})->name('dashboard');


Route::get('/callback', function () {
    $state = request('state');
    $sessionState = Session::get('state');

    $code = request('code');

    // check state integrity
    if (!$state || $state !== $sessionState || !$code) {
        abort(Response::HTTP_FORBIDDEN, 'State mismatch or not authenticated');
    }

    $factory = new Factory();
    $crawlers = $factory->getActiveCrawlers();

    $serviceNameSpotify = ServiceEnum::SPOTIFY->value;
    if (!isset($crawlers[$serviceNameSpotify])) {
        abort(Response::HTTP_BAD_REQUEST, "Service $serviceNameSpotify is not active!");
    }

    $crawler = $crawlers[$serviceNameSpotify];

    $username = Session::get($crawler->getType() . '_username');

    // do initial crawl
    // TODO extract to separate method/class
    $initialSetupResult = $crawler->initialSetup($username, ['code' => $code]);

    if ($initialSetupResult === CrawlerResultEnum::SESSION_ACCESS_TOKEN_ERROR) {
        abort(Response::HTTP_INTERNAL_SERVER_ERROR, CrawlerResultEnum::SESSION_ACCESS_TOKEN_ERROR->value);
    } else if ($initialSetupResult === CrawlerResultEnum::SESSION_SETUP_SUCCESS) {
        try {
            // read new username from session if now set by initial setup
            $crawler->crawlAll(Session::get($crawler->getType() . '_username'));
        } catch (Exception $exception) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, $exception->getMessage());
        }
    }

    Session::put('logged_in' . $crawler->getType(), true);

    return redirect()->route('dashboard');
});