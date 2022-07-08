<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use MeiliSearch\Client;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Update Meilisearch SearchableAttributes
        $client = new Client(config('scout.meilisearch.host'), config('scout.meilisearch.key'));
        $client->index('users')->updateSearchableAttributes(['name']);
    }
}
