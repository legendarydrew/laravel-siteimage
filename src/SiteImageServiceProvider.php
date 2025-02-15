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
use PZL\SiteImage\Facades\SiteImageFacade;

/**
 * SiteImageServiceProvider.
 */
class SiteImageServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole())
        {
            $this->publishes([
                __DIR__ . '/../config/site-images.php' => config_path('site-images.php')
            ], 'config');
            $this->publishes([
                __DIR__.'/../resources/assets' => public_path('img'),
            ], 'assets');
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the SiteImageHost to be used for images, as defined in the configuration.
        $this->app->singleton(
            'pzl.site-image.host',
            function (): object
            {
                $providerClass = 'PZL\\SiteImage\\Host\\' . config('site-images.provider');

                return new $providerClass();
            }
        );

        // Register CloudinaryWrapper as a singleton.
        // TODO can we do this conditionally?
        $this->app->singleton('pzl.site-image.cloudinary', fn(): \PZL\SiteImage\CloudinaryWrapper => new CloudinaryWrapper());

        // Register the SiteImage facade.
        $this->app->bind('site-image', fn(): \PZL\SiteImage\Facades\SiteImageFacade => new SiteImageFacade());

    }

    /**
     * @return string[]
     */
    public function provides(): array
    {
        return ['pzl.site-image.host'];
    }
}
