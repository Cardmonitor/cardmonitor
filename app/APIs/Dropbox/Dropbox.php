<?php

namespace App\APIs\Dropbox;

use Spatie\Dropbox\Client;
use League\Flysystem\Filesystem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\FlysystemDropbox\DropboxAdapter;

class Dropbox
{
    private $url;
    private $client_id;
    private $client_secret;

    public static function makeFilesystem(string $access_token, string $path) : string
    {
        Storage::extend('dropbox', function ($app, $config) use ($access_token) {
            $client = new Client($access_token);

            return new Filesystem(new DropboxAdapter($client));
        });

        Storage::disk('dropbox')->makeDirectory($path);

        return $path;
    }

    public function __construct()
    {
        $this->url = 'https://api.dropbox.com/';
        $this->client_id = config('services.dropbox.client_id');
        $this->client_secret = config('services.dropbox.client_secret');
    }

    public function refresh(string $refresh_token): array
    {
        $response = Http::baseUrl($this->url)
            ->withBasicAuth($this->client_id, $this->client_secret)
            ->post('oauth2/token?grant_type=refresh_token&refresh_token=' . $refresh_token);

        return $response->json();
    }
}