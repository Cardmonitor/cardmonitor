<?php

namespace App\Models\Apis;

use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class Api extends Model
{
    protected $dates = [
        'articles_synced_at',
        'invalid_at',
        'orders_synced_at',
    ];

    protected $guarded = [
        'id',
    ];

    public function isConnected() : bool
    {
        return (! is_null($this->attributes['access_token']));
    }

    public function isDeletable() : bool
    {
        return true;
    }

    public function reset() : self
    {
        $this->update([
            'access_token_secret' => null,
            'access_token' => null,
            'app_secret' => null,
            'app_token' => null,
            'invalid_at' => null,
            'request_token' => null,
        ]);

        return $this;
    }

    public function setAccessToken(string $request_token, string $access_token, string $access_token_secret)
    {
        $this->update([
            'request_token' => $request_token,
            'access_token' => $access_token,
            'access_token_secret' => $access_token_secret,
            'invalid_at' => now()->addHours(24),
        ]);
    }

    public function getAccessdataAttribute() : array
    {
        return [
            'access_token_secret' => $this->access_token_secret,
            'access_token' => $this->access_token,
            'app_secret' => $this->app_secret,
            'app_token' => $this->app_token,
            'request_token' => $this->request_token,
        ];
    }

    public function getRequestTokenAttribute()
    {
        return is_null($this->attributes['request_token']) ? null : Crypt::decryptString($this->attributes['request_token']);
    }

    public function getAppTokenAttribute()
    {
        return is_null($this->attributes['app_token']) ? null : Crypt::decryptString($this->attributes['app_token']);
    }

    public function getAppSecretAttribute()
    {
        return is_null($this->attributes['app_secret']) ? null : Crypt::decryptString($this->attributes['app_secret']);
    }

    public function getAccessTokenAttribute()
    {
        return is_null($this->attributes['access_token']) ? null : Crypt::decryptString($this->attributes['access_token']);
    }

    public function getAccessTokenSecretAttribute()
    {
        return is_null($this->attributes['access_token_secret']) ? null : Crypt::decryptString($this->attributes['access_token_secret']);
    }

    public function setRequestTokenAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['request_token'] = null;
            return;
        }

        $this->attributes['request_token'] = Crypt::encryptString($value);
    }

    public function setAppTokenAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['app_token'] = null;
            return;
        }

        $this->attributes['app_token'] = Crypt::encryptString($value);
    }

    public function setAppSecretAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['app_secret'] = null;
            return;
        }

        $this->attributes['app_secret'] = Crypt::encryptString($value);
    }

    public function setAccessTokenAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['access_token'] = null;
            return;
        }

        $this->attributes['access_token'] = Crypt::encryptString($value);
    }

    public function setAccessTokenSecretAttribute($value)
    {
        if (is_null($value)) {
            $this->attributes['access_token_secret'] = null;
            return;
        }

        $this->attributes['access_token_secret'] = Crypt::encryptString($value);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
