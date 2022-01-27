<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage;

interface SiteImageHostInterface
{
    /**
     * Returns the URL of the specified cloud-hosted image.
     *
     * @param string|null $image_id
     * @param string|null $transformation
     * @param string      $format
     * @return string
     */
    public function get(?string $image_id, ?string $transformation, string $format = SiteImageFormat::JPEG): string;

    /**
     * Approve a specific image for use.
     *
     * @return mixed
     */
    public function approve(string $image_id);

    /**
     * Reject a specific image.
     *
     * @return mixed
     */
    public function reject(string $image_id);

    /**
     * Delete a specific image.
     *
     * @param string $image_id
     * @return bool
     */
    public function destroy(string $image_id): bool;

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
     * @param string $image_filename the full path to the image file.
     * @param string $cloud_folder
     * @param string|null $cloud_name
     * @param array $tags a list of tags to associate with the images.
     * @param array $transformations a list of transformation names to perform eager transformations with.
     * @param array $parameters any additional parameters.
     *
     * @return string the public ID of the uploaded image.
     */
    public function upload(string $image_filename, string $cloud_folder, string $cloud_name = null, array $tags = [], array $transformations = [], array $parameters = []): string;

    /**
     * Copy an image file to the respective storage, marking it as for moderation.
     *
     * @param string $image_filename the full path to the image file.
     * @param string $cloud_folder
     * @param string|null $cloud_name
     * @param array $tags a list of tags to associate with the images.
     * @param array $transformations a list of transformation names to perform eager transformations with.
     *
     * @return mixed
     */
    public function uploadForModeration(string $image_filename, string $cloud_folder, string $cloud_name = null, array $tags = [], array $transformations = []);

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
     * @return array
     */
    public function getTransformations(): array;
}
