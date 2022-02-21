<?php

namespace App\Http\Controllers;

use App\Factory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\View;

class ConnectServiceController extends Controller
{
    public function __construct(
        protected Factory $factory
    ) {
    }

    public function index(): \Illuminate\Contracts\View\Factory|View|Application
    {
        $factory = new Factory();
        $services = explode(',', env('ACTIVE_SERVICES'));

        $loginUrls = [];
        foreach ($services as $service) {
            $loginUrls[$service] = $factory->getSession($service)->getLoginUrl();
        }

        return view('connect', [
            'services' => $services,
            'loginUrls' => $loginUrls,
        ]);
    }
}
