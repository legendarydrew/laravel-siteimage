<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Host;

use Illuminate\Filesystem\Filesystem;
use Intervention\Image\Exception\NotReadableException;
use Intervention\Image\Facades\Image;
use PZL\SiteImage\SiteImageFormat;
use PZL\SiteImage\SiteImageHost;
use PZL\SiteImage\SiteImageUploadResponse;

/**
 * LocalImage
 * This class is intended for hosting images locally: primarily used for development
 * and to avoid "polluting" cloud hosting.
 */
class LocalImageHost extends SiteImageHost
{
    private const TAG_FILE = 'tags.json';

    public function get(?string $image_id, string $transformation = null, string $format = SiteImageFormat::JPEG): string
    {
        if ($image_id) {
            $file = $this->getFolder() . $image_id;
            try {
                return $this->transform($file, $transformation, $format);
            } catch (NotReadableException $e) {
                // Return the [transformed] placeholder image.
                return $this->transformPlaceholder($transformation);
            }
        }

        return $this->transformPlaceholder($transformation);
    }

    public function approve(string $image_id)
    {
        // Nothing to do here: the image has been uploaded.
    }

    public function reject(string $image_id)
    {
        // Delete the image.
        $this->destroy($image_id);
    }

    public function destroy(string $image_id): bool
    {
        $fs = new Filesystem();

        return $fs->delete([
            $this->getFolder() . '**/' . basename($image_id),
            $this->getFolder() . $image_id,
        ]);
    }

    public function destroyAll(string $tag = null): void
    {
        if ($tag) {
            $images = $this->getTaggedImages($tag);
            foreach ($images as $public_id) {
                $this->destroy($public_id);
            }
        } else {
            $fs = new Filesystem();
            $fs->cleanDirectory($this->getFolder());
        }
    }

    public function upload(string $image_filename, string $cloud_folder = null, string $cloud_name = null, array $tags = [], array $transformations = [], array $parameters = []): SiteImageUploadResponse
    {
        $filename = $this->sanitiseFilename(($cloud_folder ? "$cloud_folder--" : '') . basename($cloud_name ?? $image_filename));
        if (!$extension = pathinfo($filename, PATHINFO_EXTENSION)) {
            $extension = 'png';
            $filename .= ".$extension";
        }

        // Copy the file over to the defined folder.
        // If a file with the same name exists, give the uploaded file a new name.
        if (file_exists($this->getFolder() . $filename)) {
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
        // TODO resize the image to the maximum defined size.
        $image = Image::make($image_filename)->save($this->getFolder() . $filename, null, $extension);

        // Add any specified tags.
        $this->setImageTags($filename, $tags);

        // Perform any necessary transformations.
        foreach ($transformations as $transformation) {
            $this->transform($image_filename, $transformation);
        }

        // Return a makeshift response.
        $response = new SiteImageUploadResponse();
        $response->public_id = $filename;
        $response->width = $image->width();
        $response->height = $image->height();
        $response->format = $extension;
        $response->resource_type = 'image';
        $response->created_at = now()->toISOString();
        $response->bytes = $image->filesize();
        $response->type = 'upload';
        $response->url = $this->transform($image_filename);
        $response->secure_url = $this->transform($image_filename);

        return $response;
    }

    public function uploadForModeration(string $image_filename, string $cloud_folder = null, string $cloud_name = null, array $tags = [], array $transformations = []): SiteImageUploadResponse
    {
        // TODO perhaps keep a list of images to be moderated.
        return $this->upload($image_filename, $cloud_folder, $cloud_name, $tags, $transformations);
    }

    public function tagged(string $tag): array
    {
        return $this->getTaggedImages($tag);
    }

    /**
     * Returns the path to the configured image upload folder.
     * @return string
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
     * @param bool $with_sep
     *
     * @return string
     */
    protected function createFolder(string $subdirectory = null, bool $with_sep = true): string
    {
        if ($subdirectory) {
            $subdirectory = str_replace('/', DIRECTORY_SEPARATOR, $subdirectory);
            $subdirectory = str_replace('\\', DIRECTORY_SEPARATOR, $subdirectory);
            $subdirectory = DIRECTORY_SEPARATOR . $subdirectory;
        }

        $path = sprintf('uploads%s%s', $subdirectory, ($with_sep ? DIRECTORY_SEPARATOR : ''));
        $folder_path = public_path($path);
        @mkdir($folder_path, 0755, true);

        return $folder_path;
    }

    /**
     * Returns a sanitised version of the specified filename.
     *
     * @param string $filename
     * @return array|string|string[]|null
     */
    protected function sanitiseFilename(string $filename)
    {
        return preg_replace('/[^a-z0-9.-]/', '', strtolower($filename));
    }

    /**
     * Returns the URL of the transformed specified image.
     *
     * @param string $image_file
     * @param string|null $transformation
     * @param string $format
     * @return string
     */
    protected function transform(string $image_file, string $transformation = null, string $format = SiteImageFormat::JPEG): string
    {
        // Load the image (and check whether it is an image).
        $image = Image::make($image_file);

        if ($transformation) {
            // For simplicity, we're only concerned about the width and height of the transformation
            // in this provider.
            $config = config('site-images.transformations')[$transformation];
            $target_file = sprintf('%s%s/%s', $this->getFolder(), $transformation, basename($image_file));
            @mkdir($this->getFolder() . $transformation, 0x755, true);

            if ( isset($config['width']) || isset($config['height']) ) {
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
     */
    protected function transformPlaceholder(string $transformation = null): string
    {
        $png_placeholder = public_path('img/ph/placeholder.png');
        $jpg_placeholder = public_path('img/ph/placeholder.jpg');
        if ( file_exists($jpg_placeholder) ) {
            return $this->transform($jpg_placeholder, $transformation);
        } else if ( file_exists($png_placeholder) ) {
            return $this->transform($png_placeholder, $transformation);
        }

        return '';
    }

    /**
     * Define tags for the specified image.
     * Tags along with associated image IDs are stored in a JSON file.
     */
    protected function setImageTags(string $public_id, array $tags)
    {
        $tag_file = $this->getFolder() . self::TAG_FILE;
        $tag_list = file_exists($tag_file) ? json_decode(file_get_contents($tag_file), true) : [];

        foreach ($tags as $tag) {
            if (!array_key_exists($tag, $tag_list)) {
                $tag_list[$tag] = [$public_id];
            } else {
                $tag_list[$tag][] = $public_id;
                $tag_list[$tag] = array_unique($tag_list[$tag]);
                sort($tag_list[$tag]);
            }
        }

        file_put_contents($tag_file, json_encode($tag_list));
    }

    /**
     * Returns a list of images tagged with the specified tag.
     *
     * @param string $tag
     * @return array
     */
    protected function getTaggedImages(string $tag): array
    {
        $tag_file = $this->getFolder() . self::TAG_FILE;
        if (file_exists($tag_file)) {
            $tags = json_decode(file_get_contents($tag_file), true);

            return array_key_exists($tag, $tags) ? $tags[$tag] : [];
        }

        return [];
    }
}
