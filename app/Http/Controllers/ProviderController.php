<?php

namespace App\Http\Controllers;

use App\Auth\Provider;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;

class ProviderController extends Controller
{
    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback(string $provider)
    {
        $provider_user = Socialite::driver($provider)->user();
        $provider_id = $provider_user->getId() ?? $provider_user->getNickname();

        $user = auth()->user();

        $user->providers()->updateOrCreate([
            'provider_type' => $provider,
            'provider_id' => $provider_id,
        ], [
            'email' => $provider_user->email,
            'name' => $provider_user->name,
            'token' => $provider_user->token,
            'token_secret' => null, // only available on OAuth1: $provider_user->tokenSecret,
            'refresh_token' => $provider_user->refreshToken, // only available on OAuth2
            'expires_in' => $provider_user->expiresIn, // only available on OAuth2
            'expires_at' => ($provider_user->expiresIn ? now()->addSeconds($provider_user->expiresIn) : null), // only available on OAuth2
        ]);

        return redirect('/user/settings');
    }

    public function destroy(Request $request, Provider $provider)
    {
        auth()->user()->providers()->where('id', $provider->id)->delete();

        return back()->with([
            'type' => 'success',
            'text' => 'Verbindung <b>' . $provider->provider_type . '</b> gelöscht.',
        ]);
    }
}
