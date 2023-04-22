<?php

namespace App\Providers;

use Carbon\Carbon;
use Cardmonitor\Cardmarket\Api;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('CardmarketApi', function ($app, array $parameters) {

            $access = [
                'url' => Api::URL_API,
            ];

            if (Arr::has($parameters, 'api')) {
                $access += $parameters['api']->accessdata;
            }
            else {
                $access += [
                    'app_token' => config('services.cardmarket.app_token'),
                    'app_secret' => config('services.cardmarket.app_secret'),
                    'access_token' => config('services.cardmarket.access_token'),
                    'access_token_secret' => config('services.cardmarket.access_token_secret'),
                ];
            }


            return new Api($access, [
                'timeout' => 30,
            ]);
        });

        $this->app->singleton('SkryfallApi', function ($app, array $parameters) {
            return new \Cardmonitor\Skryfall\Api();
        });

        $this->app->singleton(\App\Support\BackgroundTasks::class, function ($app, array $parameters) {
            return \App\Support\BackgroundTasks::make();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Validator::extend('formated_number', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[0-9]+,?[0-9]*$/', $value);
        });

        Carbon::setLocale($this->app->getLocale());
    }
}
