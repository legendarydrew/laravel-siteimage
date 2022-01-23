<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage;

/**
 * SiteImageHost
 * A service for handling images for a specific kind of host.
 * This service does not deal with the Image model.
 */
abstract class SiteImageHost implements SiteImageHostInterface
{
    /**
     * Build images transformations based on our configuration.
     * This is mainly for the benefit of cloud-hosted images (i.e. Cloudinary).
     *
     * @return void
     */
    public function buildTransformations(): void
    {
    }

    /**
     * Returns a set of configured image transformations.
     * @return array
     */
    public function getTransformations(): array
    {
        return config('site-images.transformations');
    }
}
