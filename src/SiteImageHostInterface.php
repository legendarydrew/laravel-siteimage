<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage;

interface SiteImageHostInterface
{
    /**
     * Returns the URL of the specified image, with any defined transformation applied.
     *
     * @param string|null $public_id
     * @param string|null $transformation [optional] the configured transformation to apply.
     * @param string      $format         [optional] the resulting image format.
     * @return string
     */
    public function get(?string $public_id, ?string $transformation, string $format = SiteImageFormat::JPEG): string;

    /**
     * Approve the specified image for general use.
     *
     * @param string $public_id
     * @return mixed
     */
    public function approve(string $public_id);

    /**
     * Returns a list of all uploaded assets (images).
     *
     * @param bool $with_tags [optional] whether to include each image's associated tags in the list.
     * @return SiteImageUploadResponse[]
     */
    public function allAssets(bool $with_tags = FALSE): array;

    /**
     * Reject the specified image.
     *
     * @param string $public_id
     * @return mixed
     */
    public function reject(string $public_id);

    /**
     * Delete the specified image.
     *
     * @param string $public_id
     * @return bool
     */
    public function destroy(string $public_id): bool;

    /**
     * Delete all images.
     *
     * @param string|null $tag [optional] only delete images with the specified tag.
     * @return mixed
     */
    public function destroyAll(?string $tag);

    /**
     * Copy an image file to the respective storage.
     *
     * @param string      $image_filename  the full path to the image file.
     * @param string      $cloud_folder
     * @param string|null $cloud_name
     * @param array       $tags            a list of tags to associate with the images.
     * @param array       $transformations a list of transformation names to perform eager transformations with.
     * @param array       $parameters      any additional parameters.
     *
     * @return SiteImageUploadResponse details about the uploaded image.
     */
    public function upload(string $image_filename, string $cloud_folder, string $cloud_name = null, array $tags = [], array $transformations = [], array $parameters = []): SiteImageUploadResponse;

    /**
     * Copy an image file to the respective storage, marking it as for moderation.
     *
     * @param string      $image_filename  the full path to the image file.
     * @param string      $cloud_folder
     * @param string|null $cloud_name
     * @param array       $tags            a list of tags to associate with the images.
     * @param array       $transformations a list of transformation names to perform eager transformations with.
     *
     * @return SiteImageUploadResponse details about the uploaded image.
     */
    public function uploadForModeration(string $image_filename, string $cloud_folder, string $cloud_name = null, array $tags = [], array $transformations = []): SiteImageUploadResponse;

    /**
     * Returns a list of cloud-hosted images matching a specific tag, within the current context.
     *
     * @param string $tag The tag to look for.
     *
     * @return mixed
     */
    public function tagged(string $tag);

    /**
     * Build images transformations based on our configuration.
     * This is mainly for the benefit of cloud-hosted images (i.e. Cloudinary).
     *
     * @return void
     */
    public function buildTransformations(): void;

    /**
     * Returns a set of configured image transformations.
     *
     * @return array
     */
    public function getTransformations(): array;
}
