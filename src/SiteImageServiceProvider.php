<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage;

use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Support\ServiceProvider;

/**
 * SiteImageServiceProvider.
 */
class SiteImageServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole())
        {
            $this->publishes([
                __DIR__ . '/../config/site-images.php' => config_path('site-images.php')
            ], 'config');
            $this->publishes([
                __DIR__.'/../resources/assets' => public_path('img/ph'),
            ], 'assets');
        }
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(
            'pzl.site-image.host',
            function ()
            {
                $providerClass = 'PZL\\SiteImage\\Host\\' . config('images.provider');

                return new $providerClass();
            }
        );

        $this->app->singleton('pzl.site-image.cloudinary', function ($app) {
            return new CloudinaryWrapper();
        });

    }

    /**
     * @return string[]
     */
    public function provides(): array
    {
        return ['pzl.site-image-host'];
    }
}
