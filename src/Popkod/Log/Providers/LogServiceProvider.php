<?php

namespace Popkod\Log\Providers;

use Illuminate\Support\ServiceProvider;
use App;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        App::bind('log', function()
        {
            return new \Popkod\Log\Log;
        });
    }
}