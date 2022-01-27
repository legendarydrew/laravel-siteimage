<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Host;

use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Exception\GeneralError;
use Cloudinary\Exception\Error;
use Exception;
use Illuminate\Http\Response;
use PZL\Http\ResponseCode;
use PZL\SiteImage\SiteImageFormat;
use PZL\SiteImage\SiteImageHost;
use PZL\SiteImage\CloudinaryWrapper;

/**
 * CloudinaryImage.
 */
class CloudinaryImageHost extends SiteImageHost
{
    /**
     * @var CloudinaryWrapper
     */
    private $wrapper;


    public function __construct()
    {
        $this->wrapper = new CloudinaryWrapper();
    }

    public function getCloudinaryWrapper(): CloudinaryWrapper
    {
        return $this->wrapper;
    }

    /**
     * buildTransformations()
     * Build images transformations based on our configuration.
     *
     * @throws Exception
     */
    public function buildTransformations(): void
    {
        $api = $this->getCloudinaryWrapper()->getApi();

        // Upload our placeholder image.
        $this->uploadPlaceholderImage();

        // Update or create our image transformations, as defined in the image configuration file.
        $transformations = $this->getTransformations();
        foreach ($transformations as $name => $settings) {
            try {
                // Attempt to UPDATE an existing transformation.
                $api->updateTransformation($name, ['allowed_for_strict' => 1], $settings);
            } catch (Exception $e) {
                // Attempt to CREATE the transformation.
                $api->createTransformation($name, $settings, ['allowed_for_strict' => 1]);
            }
        }
    }

    /**
     * uploadPlaceholderImage().
     * Uploads the placeholder image to Cloudinary.
     *
     * @throws Exception
     */
    public function uploadPlaceholderImage()
    {
        // NOTE: For the benefit of our current deployment method,
        // the placeholder image to use must have been copied to the asset folder.
        $placeholder_image = public_path('assets/img/ph/placeholder.png');
        if (!$placeholder_image) {
            abort(ResponseCode::RESPONSE_PRECONDITION_FAILED, 'No placeholder image available!');
        }
        $this->upload($placeholder_image, null, 'placeholder');
    }

    /**
     * @param string      $image_id
     * @param string|null $transformation
     * @param string      $format
     * @return string
     */
    public function get(string $image_id, string $transformation = null, string $format = SiteImageFormat::JPEG): string
    {
        $parameters = [
            'format' => $format,
            'transformation' => $transformation,
        ];

        return $this->getCloudinaryWrapper()->show($image_id, $parameters);
    }

    /**
     * @throws Exception
     */
    public function upload(string $image_filename, string $cloud_folder, string $cloud_name = null, array $tags = [], array $transformations = [], array $parameters = []): string
    {
        $parameters['folder'] = $cloud_folder;

        // Set up any "eager" transformations for the image.
        // Eager transformations are versions of the image created immediately, instead of on request.
        if (count($transformations)) {
            $parameters['eager'] = array_map(function ($transformation) {
                return ['transformation' => $transformation];
            }, $transformations);
            $parameters['eager_async'] = true;
        }

        // Upload the image!
        $wrapper = $this->getCloudinaryWrapper()->upload($image_filename, $cloud_name, $tags, $parameters);

        // Return the public ID of the image.
        return $wrapper->getPublicId();
    }

    /**
     * @throws Exception
     */
    public function uploadForModeration(string $image_filename, string $cloud_folder, ?string $cloud_name, array $tags = [], array $transformations = [])
    {
        $parameters = [
            'folder'     => $cloud_folder,
            'moderation' => 'manual',
        ];

        return $this->upload($image_filename, $cloud_folder, $cloud_name, $tags, $transformations, $parameters);
    }

    /**
     * @param string $image_id
     * @return array
     */
    public function approve(string $image_id): array
    {
        return $this->getCloudinaryWrapper()->getApi()
                       ->update($image_id, ['moderation_status' => 'approved'])->getArrayCopy();
    }

    /**
     * @param string $image_id
     * @return array
     */
    public function reject(string $image_id): array
    {
        return $this->getCloudinaryWrapper()->getApi()
                       ->update($image_id, ['moderation_status' => 'rejected'])->getArrayCopy();
    }

    /**
     * @param string $image_id
     * @return bool
     */
    public function destroy(string $image_id): bool
    {
        $output = $this->getCloudinaryWrapper()->destroyImage($image_id, ['invalidate' => true]);

        return 'ok' === $output['result'];
    }

    /**
     * Returns a list of cloud-hosted images matching a specific tag, within the current context.
     *
     * @param string $tag The tag to look for.
     *
     * @return mixed
     */
    public function tagged(string $tag)
    {
        $params = [
            'context'     => true,
            'max_results' => 500,
        ];
        $rows = [];

        try {
            do {
                $response = $this->getCloudinaryWrapper()->getApi()
                                    ->assetsByTag($tag, $params);
                $rows += $response['resources'];

                if (property_exists($response, 'next_cursor')) {
                    $params['next_cursor'] = $response['next_cursor'];
                } else {
                    break;
                }
            } while (true);
        } catch (Error $error) {
            $error_message = sprintf('Cloudinary error: [%u] %s', $error->getCode(), $error->getMessage());
            abort(ResponseCode::RESPONSE_INTERNAL_SERVER_ERROR, $error_message);
        }

        return $rows;
    }

    /**
     * @throws GeneralError
     * @throws ApiError
     */
    public function destroyAll(string $tag = null)
    {
        // Fetch a list of images matching the respective context, along with any tags they've been assigned.
        // This is so we end up removing the correct images!
        $params = [
            'tags'        => true,
            'max_results' => 500,
        ];
        $public_ids = [];
        do {
            $response = $this->getCloudinaryWrapper()->getApi()->assets($params);

            // Make a list of public IDs. If a tag was specified, we only include images with that tag.
            foreach ($response['resources'] as $row) {
                if (is_null($tag) || in_array($tag, $row['tags'])) {
                    $public_ids[] = $row['public_id'];
                }
            }

            if (property_exists($response, 'next_cursor')) {
                $params['next_cursor'] = $response['next_cursor'];
            } else {
                break;
            }
        } while (true);

        // Delete the images in batches of 100 (a limitation of the Cloudinary API).
        $chunks = array_chunk($public_ids, 100);
        foreach ($chunks as $chunk) {
            $this->getCloudinaryWrapper()->getApi()
                    ->deleteAssets($chunk);
        }
    }
}
