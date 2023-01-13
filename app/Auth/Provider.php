<?php

namespace App\Auth;

use App\APIs\Dropbox\Dropbox;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $dates = [
        'expires_at',
    ];

    protected $fillable = [
        'user_id',
        'provider_type',
        'provider_id',
        'token',
        'token_secret',
        'refresh_token',
        'expires_in',
        'expires_at',
    ];

    public function refresh()
    {
        switch ($this->provider_type) {
            case 'dropbox':
                return $this->refreshDropboxToken();
                break;

            default:
                return false;
                break;
        }
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    protected function refreshDropboxToken() : bool
    {
        $token = $this->getDropboxRefreshToken($this->refresh_token);
        $this->update([
            'token' => $token['access_token'],
            'expires_in' => $token['expires_in'],
            'expires_at' => now()->addSeconds($token['expires_in']),
        ]);

        return true;
    }

    protected function getDropboxRefreshToken(string $refresh_token) : array
    {
        $dropbox_api = new Dropbox();

        return $dropbox_api->refresh($refresh_token);
    }
}
