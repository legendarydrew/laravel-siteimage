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
     */
    public function buildTransformations(): void
    {
    }

    /**
     * Returns the list of image transformations, as defined in the configuration.
     */
    public function getTransformations(): array
    {
        return config('site-images.transformations');
    }

    /**
     * Returns the URL of the defined placeholder image, with any defined transformation applied.
     *
     * @param string|null $transformation
     */
    public function getPlaceholder(string $transformation = null): ?string {
        return $this->get(null, $transformation);
    }

    /**
     * Returns a sanitised version of the specified filename.
     *
     * @return array|string|string[]|null
     */
    protected function sanitiseFilename(string $filename)
    {
        return preg_replace('/[^a-z0-9.-]/', '', strtolower($filename));
    }

    /**
     * Rename an existing uploaded image.
     */
    abstract public function rename(string $public_id, string $new_public_id, bool $overwrite = false): SiteImageUploadResponse;
}
