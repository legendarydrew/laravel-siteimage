<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Host;

use Illuminate\Filesystem\Filesystem;
use Intervention\Image\Drivers\GD\Driver;
use Intervention\Image\Exceptions\InvalidArgumentException;
use Intervention\Image\ImageManager;
use PZL\Http\ResponseCode;
use PZL\SiteImage\SiteImageFormat;
use PZL\SiteImage\SiteImageHost;
use PZL\SiteImage\SiteImageUploadResponse;

/**
 * LocalImageHost.
 * This class is intended for hosting images locally: primarily used for development
 * and to avoid "polluting" cloud hosting.
 */
class LocalImageHost extends SiteImageHost
{
    private const TAG_FILE = 'tags.json';

    /**
     * @param string|null $public_id
     * @param string|null $transformation
     * @param string      $format
     * @return string
     * @throws InvalidArgumentException
     */
    public function get(string $public_id = null, string $transformation = null, string $format = SiteImageFormat::JPEG): string
    {
        if ($public_id)
        {
            $file = $this->getFolder() . $public_id;
            try
            {
                return $this->transform($file, $transformation, $format);
            }
            catch (\Exception)
            {
                // Return the [transformed] placeholder image.
                return $this->transformPlaceholder($transformation);
            }
        }

        return $this->transformPlaceholder($transformation);
    }

    /**
     * @param string $public_id
     * @return void
     */
    public function approve(string $public_id): void
    {
        // Nothing to do here: the image has been uploaded.
    }

    /**
     * @param string $public_id
     * @return void
     */
    public function reject(string $public_id): void
    {
        // Delete the image.
        $this->destroy($public_id);
    }

    /**
     * @param string $public_id
     * @return bool
     */
    public function destroy(string $public_id): bool
    {
        $filesystem = new Filesystem();

        return $filesystem->delete([
            $this->getFolder() . '**/' . basename($public_id),
            $this->getFolder() . $public_id,
        ]);
    }

    /**
     * @param string|null $tag
     * @return void
     */
    public function destroyAll(string $tag = null): void
    {
        if ($tag)
        {
            $images = $this->getTaggedImages($tag);
            foreach ($images as $image)
            {
                $this->destroy($image);
            }
        }
        else
        {
            $filesystem = new Filesystem();
            $filesystem->cleanDirectory($this->getFolder());
        }
    }

    /**
     * @param string      $image_filename
     * @param string|null $cloud_folder
     * @param string|null $cloud_name
     * @param array       $tags
     * @param array       $transformations
     * @param array       $parameters
     * @return SiteImageUploadResponse
     * @throws InvalidArgumentException
     */
    public function upload(string $image_filename, string $cloud_folder = null, string $cloud_name = null, array $tags = [], array $transformations = [], array $parameters = []): SiteImageUploadResponse
    {
        $filename = $this->sanitiseFilename(($cloud_folder ? $cloud_folder . '--' : '') . pathinfo($cloud_name ?? $image_filename, PATHINFO_BASENAME));
        $extension = str_replace('.', '', pathinfo($filename, PATHINFO_EXTENSION));
        if (empty($extension))
        {
            $extension = 'png';
            $filename  .= '.' . $extension;
        }

        // Copy the file over to the defined folder.
        // If a file with the same name exists, give the uploaded file a new name.
        $overwrite = $parameters['overwrite'] ?? false;
        if (!$overwrite && file_exists($this->getFolder() . $filename))
        {
            $filename = sprintf(
                '%s_%s.%s',
                pathinfo($filename, PATHINFO_FILENAME),
                date('Ymdhis'),
                $extension
            );
        }

        // Thanks to the Intervention package, we should be able to handle different kinds of images:
        // - image files;
        // - Base64-encoded data;
        // - image URLs.
        $manager = ImageManager::usingDriver(Driver::class);
        $image   = $manager->decode($image_filename)
                           ->save($this->getFolder() . $filename);

        // Add any specified tags.
        $this->setImageTags($filename, $tags);

        // Perform any necessary transformations.
        foreach ($transformations as $transformation)
        {
            $this->transform($image_filename, $transformation);
        }

        // Return a makeshift response.
        return new SiteImageUploadResponse([
            'public_id'     => $filename,
            'width'         => $image->width(),
            'height'        => $image->height(),
            'format'        => $extension,
            'resource_type' => 'image',
            'created_at'    => now()->toISOString(),
            'bytes'         => $image->count(),
            'type'          => 'upload',
            'url'           => $this->transform($image_filename),
            'secure_url'    => $this->transform($image_filename)
        ]);
    }

    /**
     * @param string      $image_filename
     * @param string|null $cloud_folder
     * @param string|null $cloud_name
     * @param array       $tags
     * @param array       $transformations
     * @return SiteImageUploadResponse
     * @throws InvalidArgumentException
     */
    public function uploadForModeration(string $image_filename, string $cloud_folder = null, string $cloud_name = null, array $tags = [], array $transformations = []): SiteImageUploadResponse
    {
        return $this->upload($image_filename, $cloud_folder, $cloud_name, $tags, $transformations);
    }

    /**
     * @param string $tag
     * @return array
     */
    public function tagged(string $tag): array
    {
        return $this->getTaggedImages($tag);
    }

    /**
     * Returns the path to the configured image upload folder.
     */
    public function getFolder(): string
    {
        return $this->createFolder(config('site-images.local.folder'));
    }

    /**
     * createFolder()
     * Create and returns a path in the configured upload directory, with trailing slash.
     *
     * @param string|null $subdirectory
     *
     */
    protected function createFolder(string $subdirectory = null, bool $with_sep = true): string
    {
        if ($subdirectory)
        {
            $subdirectory = str_replace('/', DIRECTORY_SEPARATOR, $subdirectory);
            $subdirectory = str_replace('\\', DIRECTORY_SEPARATOR, $subdirectory);
            $subdirectory = DIRECTORY_SEPARATOR . $subdirectory;
        }

        $path        = sprintf('uploads%s%s', $subdirectory, ($with_sep ? DIRECTORY_SEPARATOR : ''));
        $folder_path = public_path($path);
        @mkdir($folder_path, 0755, true);

        return $folder_path;
    }

    /**
     * Returns the URL of the transformed specified image.
     *
     * @param string|null $transformation
     * @throws InvalidArgumentException
     */
    protected function transform(string $image_file, string $transformation = null, string $format = SiteImageFormat::JPEG): string
    {
        // Load the image (and check whether it is an image).
        $manager = ImageManager::usingDriver(Driver::class);
        $image   = $manager->decode($image_file);

        if ($transformation)
        {
            // For simplicity, we're only concerned about the width and height of the transformation
            // in this provider.
            $transformations = $this->getTransformations();
            if (!isset($transformations[$transformation]))
            {
                abort(ResponseCode::RESPONSE_BAD_REQUEST, 'Invalid image transformation.');
            }

            $config      = $transformations[$transformation];
            $target_file = sprintf('%s%s/%s', $this->getFolder(), $transformation, basename($image_file));
            @mkdir($this->getFolder() . $transformation, 0x755, true);

            if (isset($config['width']) || isset($config['height']))
            {
                $image->resize($config['width'] ?? null, $config['height'] ?? null);
            }

            $image->save($target_file, null, $format);

            return asset(str_replace(public_path(), '', $target_file));
        }

        // Return a URL to the non-transformed image.
        return asset(str_replace(public_path(), '', $image_file));
    }

    /**
     * Returns the URL of a transformed placeholder image.
     *
     * @param string|null $transformation
     * @return string
     * @throws InvalidArgumentException
     */
    protected function transformPlaceholder(string $transformation = null): string
    {
        $placeholder = public_path(config('site-images.default_image'));
        if (file_exists($placeholder))
        {
            return $this->transform($placeholder, $transformation);
        }

        return '';
    }

    /**
     * Define tags for the specified image.
     * Tags along with associated image IDs are stored in a JSON file.
     */
    protected function setImageTags(string $public_id, array $tags): void
    {
        $tag_file = $this->getFolder() . self::TAG_FILE;
        $tag_list = file_exists($tag_file) ? json_decode(file_get_contents($tag_file), true) : [];

        foreach ($tags as $tag)
        {
            if (!array_key_exists($tag, $tag_list))
            {
                $tag_list[$tag] = [$public_id];
            }
            else
            {
                $tag_list[$tag][] = $public_id;
                $tag_list[$tag]   = array_unique($tag_list[$tag]);
                sort($tag_list[$tag]);
            }
        }

        file_put_contents($tag_file, json_encode($tag_list));
    }

    /**
     * Returns a list of images tagged with the specified tag.
     */
    protected function getTaggedImages(string $tag): array
    {
        $tag_file = $this->getFolder() . self::TAG_FILE;
        if (file_exists($tag_file))
        {
            $tags = json_decode(file_get_contents($tag_file), true);

            return array_key_exists($tag, $tags) ? $tags[$tag] : [];
        }

        return [];
    }

    /**
     * @param string $public_id
     * @return array
     */
    protected function getImageTags(string $public_id): array
    {
        $tag_list = [];
        $tag_file = $this->getFolder() . self::TAG_FILE;
        if (file_exists($tag_file))
        {
            $tags = json_decode(file_get_contents($tag_file), true);
            foreach ($tags as $tag => $images)
            {
                if (in_array($public_id, $images))
                {
                    $tag_list[] = $tag;
                }
            }
        }

        return $tag_list;
    }

    /**
     * @param bool $with_tags
     * @return array|SiteImageUploadResponse[]
     * @throws InvalidArgumentException
     */
    public function allAssets(bool $with_tags = false): array
    {
        $files = glob($this->getFolder() . '/*.{jpg,png}', GLOB_BRACE);
        return array_map(/**
         * @throws InvalidArgumentException
         */ function ($row) use ($with_tags): SiteImageUploadResponse
        {
            $public_id = basename($row);
            $extension = str_replace('.', '', pathinfo($row, PATHINFO_EXTENSION));
            $manager   = ImageManager::usingDriver(Driver::class);
            $image     = $manager->decode($row);
            return new SiteImageUploadResponse([
                'public_id'         => $public_id,
                'width'             => $image->width(),
                'height'            => $image->height(),
                'format'            => $extension,
                'resource_type'     => 'image',
                'created_at'        => filectime($row),
                'tags'              => $with_tags ? $this->getImageTags($public_id) : [],
                'bytes'             => $image->count(),
                'type'              => '',
                'placeholder'       => false,
                'url'               => $this->get($public_id),
                'secure_url'        => $this->get($public_id),
                'original_filename' => $public_id
            ]);
        }, $files);
    }

    /**
     * @param string $public_id
     * @param string $new_public_id
     * @param bool   $overwrite
     * @return SiteImageUploadResponse
     */
    public function rename(string $public_id, string $new_public_id, bool $overwrite = false): SiteImageUploadResponse
    {
        // Check if the image exists.
        $original_file = $this->getFolder() . $public_id;
        if (!file_exists($original_file))
        {
            abort(ResponseCode::RESPONSE_BAD_REQUEST, 'Image not found.');
        }

        // Check if the new image exists.
        $new_file = $this->getFolder() . $new_public_id;
        if (file_exists($new_file))
        {
            if (!$overwrite)
            {
                abort(ResponseCode::RESPONSE_PRECONDITION_FAILED, 'Image with the new ID already exists.');
            }
            else
            {
                $this->destroy($new_public_id);
            }
        }

        // Rename the image.
        $new_public_id = $this->sanitiseFilename($new_public_id);
        rename($original_file, $this->getFolder() . $new_public_id);

        // Remove any transformed images.
        (new Filesystem())->delete([
            $this->getFolder() . '**/' . basename($public_id)
        ]);

        return new SiteImageUploadResponse(['public_id' => $new_public_id]);
    }
}
