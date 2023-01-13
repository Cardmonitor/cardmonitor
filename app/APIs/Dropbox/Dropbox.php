<?php

namespace App\APIs\Dropbox;

use Illuminate\Support\Facades\Http;

class Dropbox
{
    private $url;
    private $client_id;
    private $client_secret;

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