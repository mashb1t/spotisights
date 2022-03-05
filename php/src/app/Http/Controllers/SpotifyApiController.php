<?php

namespace App\Http\Controllers;

use App\Enums\ServiceEnum;
use App\Factory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use SpotifyWebAPI\Session as SpotifyWebAPISession;
use Symfony\Component\HttpFoundation\Response as ResponseCode;

class SpotifyApiController
{
    public function __construct(
        protected Factory $factory
    ) {
    }

    public function createPlaylist(Request $request)
    {
        $validated = $request->validate([
            'limit' => 'required|integer',
            'user' => 'required|string',
            'genres' => 'required|string',
        ]);

        $user = $validated['user'];
        $genres = $validated['genres'];
        $genres = $this->transformToArray($genres);

        $implodedPlaylistTitle = implode(', ', $genres);

        try {
            $session = $this->factory->getSessionHandler()->loadSession(ServiceEnum::Spotify, $user);
        } catch (\Exception $exception) {
            Log::error("Could not load session for user $user, message: " . $exception->getMessage());
            abort(ResponseCode::HTTP_UNAUTHORIZED, "Could not load session of user $user");
        }

        /** @var SpotifyWebAPISession $spotifyWebApiSession */
        $spotifyWebApiSession = $session->getUnderlyingObject();

        $spotifyApi = $this->factory->getSpotifyWebAPI($spotifyWebApiSession);

        $recommendations = $spotifyApi->getRecommendations([
            'limit' => $validated['limit'],
            'seed_genres' => $genres,
        ]);

        if (!isset($recommendations->tracks) || count($recommendations->tracks) == 0) {
            $message = "No tracks found for $implodedPlaylistTitle";
            Log::info($message);
            abort(ResponseCode::HTTP_BAD_REQUEST, $message);
        }

        $trackIds = collect($recommendations->tracks)->pluck('id')->toArray();

        Log::debug($trackIds);

        $newPlaylist = $spotifyApi->createPlaylist([
            'name' => "SpotiSights Playlist $implodedPlaylistTitle",
            'description' => "SpotiSights recommendations based on $implodedPlaylistTitle",
            'public' => false,
        ]);

        $spotifyApi->addPlaylistTracks($newPlaylist->id, $trackIds);

        return Response::json('ok');
    }

    protected function transformToArray(array|string|null $genres): array
    {
        return Str::of($genres)->ltrim('{')->rtrim('}')->explode(',')->toArray();
    }
}
