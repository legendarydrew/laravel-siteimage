<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Host;

use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\GeneralError;
use Cloudinary\Api\Exception\NotAllowed;
use Exception;
use PZL\SiteImage\CloudinaryWrapper;
use PZL\SiteImage\SiteImageFormat;
use PZL\SiteImage\SiteImageHost;
use PZL\SiteImage\SiteImageUploadResponse;

/**
 * CloudinaryImageHost.
 */
class CloudinaryImageHost extends SiteImageHost
{
    private readonly CloudinaryWrapper $cloudinaryWrapper;


    public function __construct()
    {
        $this->cloudinaryWrapper = new CloudinaryWrapper();
    }

    /**
     * buildTransformations()
     * Build images transformations based on our configuration.
     *
     * @throws Exception
     */
    public function buildTransformations(): void
    {
        $adminApi = $this->getCloudinaryWrapper()->getApi();

        // Update or create our image transformations, as defined in the image configuration file.
        $transformations = $this->getTransformations();
        foreach ($transformations as $name => $settings)
        {
            try
            {
                // Attempt to UPDATE an existing transformation.
                $adminApi->updateTransformation($name, [
                    'unsafe_update'      => $settings,
                    'allowed_for_strict' => 1
                ]);
            }
            catch (Exception)
            {
                // Attempt to CREATE the transformation.
                $adminApi->createTransformation($name, $settings);
            }
        }

        // Remove any named transformations that aren't defined in the configuration file.
        $live_transformations        = $this->getLiveTransformations();
        $not_defined_transformations = array_diff($live_transformations, array_keys($transformations));
        foreach ($not_defined_transformations as $name)
        {
            try
            {
                $adminApi->deleteTransformation($name);
            }
            catch (NotAllowed $exception)
            {
                logger()->info("Attempt to remove $name transformation:, {$exception->getMessage()}");
            }
        }

    }

    /**
     * Returns a list of named transformation names defined in Cloudinary.
     * https://cloudinary.com/documentation/admin_api#get_transformations
     *
     * @return array
     */
    public function getLiveTransformations(): array
    {
        $adminApi        = $this->getCloudinaryWrapper()->getApi();
        $transformations = [];
        $cursor          = null;
        do
        {
            $response        = $adminApi->transformations(['cursor' => $cursor, 'max_results' => 100, 'named' => true])->getArrayCopy();
            $transformations = array_merge($transformations, array_map(fn($t) => $t['name'], $response['transformations']));
            $cursor          = $response['next_cursor'] ?? null;
        }
        while ($cursor);

        return $transformations;
    }

    public function getCloudinaryWrapper(): CloudinaryWrapper
    {
        return $this->cloudinaryWrapper;
    }

    /**
     * @throws Exception
     */
    public function upload(string $image_filename, string $cloud_folder = null, string $cloud_name = null, array $tags = [], array $transformations = [], array $parameters = []): SiteImageUploadResponse
    {
        $parameters['folder'] = $cloud_folder;

        // Set up any "eager" transformations for the image.
        // Eager transformations are versions of the image created immediately, instead of on request.
        if ($transformations !== [])
        {
            $parameters['eager']       = array_map(fn($transformation): array => ['transformation' => $transformation], $transformations);
            $parameters['eager_async'] = true;
        }

        // Upload the image!
        $cloud_name        ??= $this->sanitiseFilename($image_filename);
        $cloudinaryWrapper = $this->getCloudinaryWrapper()->upload($image_filename, $cloud_name, $parameters, $tags);

        // Return the upload response.
        return SiteImageUploadResponse::fromCloudinaryWrapper($cloudinaryWrapper);
    }

    /**
     * @param string|null $public_id pass NULL to use a placeholder image.
     * @param string|null $transformation
     */
    public function get(string $public_id = null, string $transformation = null, string $format = SiteImageFormat::JPEG): string
    {
        $parameters = [
            'format'         => $format,
            'transformation' => $transformation,
        ];

        return $this->getCloudinaryWrapper()
                    ->show($public_id ?? config('site-images.default_image'), $parameters);
    }

    /**
     * @throws Exception
     */
    public function uploadForModeration(string $image_filename, string $cloud_folder = null, string $cloud_name = null, array $tags = [], array $transformations = []): SiteImageUploadResponse
    {
        $parameters = [
            'folder'     => $cloud_folder,
            'moderation' => 'manual',
        ];

        return $this->upload($image_filename, $cloud_folder, $cloud_name, $tags, $transformations, $parameters);
    }

    public function approve(string $public_id): array
    {
        return $this->getCloudinaryWrapper()->getApi()
                    ->update($public_id, ['moderation_status' => 'approved'])
                    ->getArrayCopy();
    }

    public function reject(string $public_id): array
    {
        return $this->getCloudinaryWrapper()->getApi()
                    ->update($public_id, ['moderation_status' => 'rejected'])
                    ->getArrayCopy();
    }

    public function destroy(string $public_id): bool
    {
        $output = $this->getCloudinaryWrapper()->destroyImage($public_id, ['invalidate' => true]);

        return 'ok' === $output['result'];
    }

    /**
     * @throws GeneralError
     * @throws ApiError
     */
    public function destroyAll(string $tag = null): void
    {
        $assets     = $tag ? $this->tagged($tag) : $this->allAssets();
        $public_ids = array_map(fn($row) => $row->public_id, $assets);

        // Delete the images in batches of 100 (a limitation of the Cloudinary API).
        $chunks = array_chunk($public_ids, 100);
        foreach ($chunks as $chunk)
        {
            $this->getCloudinaryWrapper()->getApi()
                 ->deleteAssets($chunk);
        }
    }

    /**
     * Returns a list of cloud-hosted images matching a specific tag, within the current context.
     *
     * @param string $tag The tag to look for.
     *
     * @return mixed[]
     */
    public function tagged(string $tag): array
    {
        $params = [
            'context'     => true,
            'max_results' => 500,
        ];
        $rows   = [];

        do
        {
            $response = $this->getCloudinaryWrapper()->getApi()->assetsByTag($tag, $params)->getArrayCopy();
            $rows     += $response['resources'];

            if (isset($response['next_cursor']))
            {
                $params['next_cursor'] = $response['next_cursor'];
            }
            else
            {
                break;
            }
        }
        while (true);

        return $rows;
    }

    /**
     * Returns a list of Cloudinary-hosted assets.
     *
     * @return SiteImageUploadResponse[]
     */
    public function allAssets(bool $with_tags = false): array
    {
        $params = [
            'tags'        => $with_tags,
            'max_results' => 500,
        ];
        $assets = [];
        do
        {
            $response = $this->getCloudinaryWrapper()->getApi()->assets($params)->getArrayCopy();

            // Make a list of public IDs. If a tag was specified, we only include images with that tag.
            foreach ($response['resources'] as $row)
            {
                $assets[] = new SiteImageUploadResponse($row);
            }

            if (isset($response['next_cursor']))
            {
                $params['next_cursor'] = $response['next_cursor'];
            }
            else
            {
                break;
            }
        }
        while (true);

        return $assets;
    }

    /**
     * Renames a Cloudinary asset.
     */
    public function rename(string $public_id, string $new_public_id, bool $overwrite = false): SiteImageUploadResponse
    {
        // https://cloudinary.com/documentation/image_upload_api_reference#rename_method
        $new_public_id = $this->sanitiseFilename($new_public_id);
        $wrapper       = $this->getCloudinaryWrapper()->rename($public_id, $new_public_id, ['overwrite' => $overwrite]);

        return new SiteImageUploadResponse($wrapper);
    }
}
