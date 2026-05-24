<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\VKontakte\VKontakteExtendSocialite;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->bootSocialite();
    }

    private function bootSocialite(): void
    {
        $this->app->make('events')->listen(
            SocialiteWasCalled::class,
            VKontakteExtendSocialite::class . '@handle'
        );
    }
}
