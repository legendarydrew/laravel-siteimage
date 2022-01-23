<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Host;

use Cloudder;
use Cloudinary\Api\Error;
use Cloudinary\Api\GeneralError;
use Cloudinary\Api\Response;
use Exception;
use PZL\Http\ResponseCode;
use PZL\SiteImage\ImageFormat;
use PZL\SiteImage\SiteImageHost;

/**
 * CloudinaryImage.
 */
class CloudinaryHost extends SiteImageHost
{
    /**
     * buildTransformations()
     * Build images transformations based on our configuration.
     *
     * @throws Exception
     */
    public function buildTransformations(): void
    {
        $api = Cloudder::getApi();

        // Upload our placeholder image.
        $this->uploadPlaceholderImage();

        // Update or create our image transformations, as defined in the image configuration file.
        $transformations = $this->getTransformations();
        foreach ($transformations as $name => $settings) {
            try {
                // Attempt to UPDATE an existing transformation.
                $api->update_transformation($name, ['allowed_for_strict' => 1], $settings);
            } catch (Exception $e) {
                // Attempt to CREATE the transformation.
                $api->create_transformation($name, $settings, ['allowed_for_strict' => 1]);
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

    public function get(string $image_id, ?string $transformation, string $format = ImageFormat::JPEG): string
    {
        $parameters = [
            'format' => $format,
            'transformation' => $transformation,
        ];

        return Cloudder::show($image_id, $parameters);
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
        $wrapper = Cloudder::upload($image_filename, $cloud_name, $tags, $parameters);

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
     * @throws GeneralError
     */
    public function approve(string $image_id): Response
    {
        return Cloudder::getApi()
                       ->update($image_id, ['moderation_status' => 'approved']);
    }

    /**
     * @throws GeneralError
     */
    public function reject(string $image_id): Response
    {
        return Cloudder::getApi()
                       ->update($image_id, ['moderation_status' => 'rejected']);
    }

    public function destroy(string $image_id): bool
    {
        $output = Cloudder::destroyImage($image_id, ['invalidate' => true]);

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
                $response = Cloudder::getApi()
                                    ->resources_by_tag($tag, $params);
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
            $response = Cloudder::getApi()->resources($params);

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
            Cloudder::getApi()
                    ->delete_resources($chunk);
        }
    }
}
