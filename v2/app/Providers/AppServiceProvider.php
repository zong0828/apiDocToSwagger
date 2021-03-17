<?php

namespace App\Providers;

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
        // Laravel Enum
        $this->app->register(\BenSampo\Enum\EnumServiceProvider::class);
    }
}
