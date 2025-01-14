<?php

namespace PZL\SiteImage;

use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Asset\Media;
use Cloudinary\Cloudinary;
use Cloudinary\Configuration\Configuration;
use Illuminate\Config\Repository;

/**
 * CloudinaryWrapper
 * Migrated from the original via https://cloudinary.com/documentation/php2_migration
 *
 * @package PZL\SiteImage
 */
class CloudinaryWrapper
{

    /**
     * Cloudinary lib.
     *
     * @var Cloudinary
     */
    protected $cloudinary;

    /**
     * Cloudinary uploader.
     *
     * @var UploadApi
     */
    protected $uploader;

    /**
     * Repository config.
     *
     * @var Repository
     */
    protected $config;

    /**
     * Uploaded result.
     *
     * @var ApiResponse
     */
    protected $uploadedResult;

    /**
     * @var AdminApi
     */
    private $api;

    /**
     * Create a new cloudinary instance.
     *
     * @return void
     */
    public function __construct()
    {
        // These are here to make this class simpler to test (with mocking).
        $this->cloudinary = Configuration::instance([
            'cloud' => [
                'cloud_name' => config('site-images.cloudinary.cloudName'),
                'api_key'    => config('site-images.cloudinary.apiKey'),
                'api_secret' => config('site-images.cloudinary.apiSecret')
            ]
        ]);
        $this->uploader   = new UploadApi();
        $this->api        = new AdminApi();
    }

    /**
     * Get cloudinary class.
     *
     * @return Cloudinary
     */
    public function getCloudinary(): Cloudinary
    {
        return $this->cloudinary;
    }

    /**
     * Get cloudinary uploader.
     *
     * @return UploadApi
     */
    public function getUploader(): UploadApi
    {
        return $this->uploader;
    }

    /**
     * Get cloudinary api
     *
     * @return AdminApi
     */
    public function getApi(): AdminApi
    {
        return $this->api;
    }


    /**
     * Upload image to cloud.
     *
     * @param mixed       $source
     * @param string|null $publicId
     * @param array       $uploadOptions
     * @param array       $tags
     * @return CloudinaryWrapper
     * @throws ApiError
     */
    public function upload($source, string $publicId = null, array $uploadOptions = [], array $tags = []): CloudinaryWrapper
    {
        $defaults = [
            'public_id' => null,
            'tags'      => []
        ];

        $options = array_merge($defaults, [
            'public_id' => $publicId,
            'tags'      => $tags
        ]);

        $options = array_merge($options, $uploadOptions);

        $this->uploadedResult = $this->getUploader()->upload($source, $options);

        return $this;
    }

    /**
     * Upload image to cloud.
     *
     * @param mixed       $source
     * @param string|null $publicId
     * @param string|null $uploadPreset
     * @param array       $uploadOptions
     * @param array       $tags
     * @return CloudinaryWrapper
     * @throws ApiError
     */
    public function unsignedUpload($source, string $publicId = null, string $uploadPreset = null,
                                   array $uploadOptions = [], array $tags = []): CloudinaryWrapper
    {
        $defaults = [
            'public_id' => null,
            'tags'      => []
        ];

        $options = array_merge($defaults, [
            'public_id' => $publicId,
            'tags'      => $tags,
        ]);

        $options              = array_merge($options, $uploadOptions);
        $this->uploadedResult = $this->getUploader()->unsignedUpload($source, $uploadPreset, $options);

        return $this;
    }

    /**
     * Upload video to cloud.
     *
     * @param mixed       $source
     * @param string|null $publicId
     * @param array       $uploadOptions
     * @param array       $tags
     * @return CloudinaryWrapper
     * @throws ApiError
     */
    public function uploadVideo($source, string $publicId = null, array $uploadOptions = [], array $tags = []): CloudinaryWrapper
    {
        $options = array_merge($uploadOptions, ['resource_type' => 'video']);
        return $this->upload($source, $publicId, $options, $tags);
    }

    /**
     * Uploaded result.
     *
     * @return ApiResponse
     */
    public function getResult(): ApiResponse
    {
        return $this->uploadedResult;
    }

    /**
     * Uploaded public ID.
     *
     * @return string
     */
    public function getPublicId(): string
    {
        return $this->uploadedResult['public_id'];
    }

    /**
     * Display resource through https.
     *
     * @param string $publicId
     * @param array  $options
     * @return string
     */
    public function show(string $publicId, array $options = []): string
    {
        if (!array_key_exists('transformation', $options))
        {
            $defaults = config('site-images.cloudinary.default', []);
            $options  = array_merge($defaults, $options);
        }

        return Media::fromParams($publicId, $options);
    }

    /**
     * Display resource through https.
     *
     * @param string $publicId
     * @param array  $options
     * @return string
     */
    public function secureShow(string $publicId, array $options = []): string
    {
        $options = array_merge(['secure' => true], $options);

        return $this->show($publicId, $options);
    }


    /**
     * Alias for privateDownloadUrl
     *
     * @param string $publicId
     * @param string $format
     * @param array  $options
     * @return string|null
     */
    public function showPrivateUrl(string $publicId, string $format, array $options = []): ?string
    {
        return $this->privateDownloadUrl($publicId, $format, $options);
    }

    /**
     * Display private image
     *
     * @param string $publicId
     * @param string $format
     * @param array  $options
     * @return string|null
     */
    public function privateDownloadUrl(string $publicId, string $format, array $options = []): ?string
    {
        return $this->getUploader()->privateDownloadUrl($publicId, $format, $options);
    }

    /**
     * Rename public ID.
     *
     * @param string $publicId
     * @param string $toPublicId
     * @param array  $options
     * @return array
     */
    public function rename(string $publicId, string $toPublicId, array $options = []): array
    {
        return $this->getUploader()->rename($publicId, $toPublicId, $options)->getArrayCopy();
    }

    /**
     * Alias for destroy
     *
     * @param string $publicId
     * @param array  $options
     * @return array
     */
    public function destroyImage(string $publicId, array $options = []): array
    {
        return $this->destroy($publicId, $options);
    }

    /**
     * Destroy resource from Cloudinary
     *
     * @param string $publicId
     * @param array  $options
     * @return array
     */
    public function destroy(string $publicId, array $options = []): array
    {
        return $this->getUploader()->destroy($publicId, $options)->getArrayCopy();
    }

    /**
     * Restore a resource
     *
     * @param array $publicIds
     * @param array $options
     * @return null
     */
    public function restore(array $publicIds = [], array $options = [])
    {
        return $this->getApi()->restore($publicIds, $options);
    }

    /**
     * Alias for deleteResources
     *
     * @param array $publicIds
     * @param array $options
     * @return null
     * @throws ApiError
     */
    public function destroyImages(array $publicIds, array $options = [])
    {
        return $this->deleteAssets($publicIds, $options);
    }

    /**
     * Destroy images from Cloudinary
     *
     * @param array $publicIds
     * @param array $options
     * @return null
     * @throws ApiError
     */
    public function deleteAssets(array $publicIds, array $options = [])
    {
        return $this->getApi()->deleteAssets($publicIds, $options);
    }

    /**
     * Destroy a resource by its prefix
     *
     * @param string $prefix
     * @param array  $options
     * @return null
     * @throws ApiError
     */
    public function deleteAssetsByPrefix(string $prefix, array $options = [])
    {
        return $this->getApi()->deleteAssetsByPrefix($prefix, $options);
    }

    /**
     * Destroy all resources from Cloudinary
     *
     * @param array $options
     * @return null
     * @throws ApiError
     */
    public function deleteAllAssets(array $options = [])
    {
        return $this->getApi()->deleteAllAssets($options);
    }

    /**
     * Delete all resources from one tag
     *
     * @param string $tag
     * @param array  $options
     * @return null
     * @throws ApiError
     */
    public function deleteAssetsByTag(string $tag, array $options = [])
    {
        return $this->getApi()->deleteAssetsByTag($tag, $options);
    }

    /**
     * Delete transformed images by IDs
     *
     * @param array $publicIds
     * @param array $options
     * @return null
     * @throws ApiError
     */
    public function deleteDerivedAssets(array $publicIds = [], array $options = [])
    {
        return $this->getApi()->deleteDerivedAssets($publicIds, $options);
    }

    /**
     * Alias of destroy.
     *
     * @param       $publicId
     * @param array $options
     * @return bool
     */
    public function delete($publicId, array $options = []): bool
    {
        $response = $this->destroy($publicId, $options);

        return (boolean)($response['result'] === 'ok');
    }

    /**
     * Add tag to images.
     *
     * @param string $tag
     * @param array  $publicIds
     * @param array  $options
     * @return ApiResponse
     */
    public function addTag(string $tag, array $publicIds = [], array $options = []): ApiResponse
    {
        return $this->getUploader()->addTag($tag, $publicIds, $options);
    }

    /**
     * Remove tag from images.
     *
     * @param string $tag
     * @param array  $publicIds
     * @param array  $options
     * @return ApiResponse
     */
    public function removeTag(string $tag, array $publicIds = [], array $options = []): ApiResponse
    {
        return $this->getUploader()->removeTag($tag, $publicIds, $options);
    }

    /**
     * Replace image's tag.
     *
     * @param string $tag
     * @param array  $publicIds
     * @param array  $options
     * @return ApiResponse
     */
    public function replaceTag(string $tag, array $publicIds = [], array $options = []): ApiResponse
    {
        return $this->getUploader()->replaceTag($tag, $publicIds, $options);
    }

    /**
     * Create a zip file containing images matching options.
     *
     * @param array       $options
     * @param string|null $nameArchive
     * @param string      $mode
     * @return ApiResponse
     */
    public function createArchive(array $options = [], string $nameArchive = null, string $mode = 'create'): ApiResponse
    {
        $options = array_merge($options, ['target_public_id' => $nameArchive, 'mode' => $mode]);
        return $this->getUploader()->createArchive($options);
    }

    /**
     * Download a zip file containing images matching options.
     *
     * @param array       $options
     * @param string|null $nameArchive
     * @return string
     */
    public function downloadArchiveUrl(array $options = [], string $nameArchive = null): string
    {
        $options = array_merge($options, ['target_public_id' => $nameArchive]);
        return $this->getUploader()->downloadArchiveUrl($options);
    }


    /**
     * Show Assets
     *
     * @param array $options
     * @return array
     */
    public function assets(array $options = []): array
    {
        return $this->getApi()->assets($options)->getArrayCopy();
    }

    /**
     * Show Resources by id
     *
     * @param array $publicIds
     * @param array $options
     * @return array
     */
    public function assetsByIds(array $publicIds, array $options = []): array
    {
        return $this->getApi()->assetsByIds($publicIds, $options)->getArrayCopy();
    }

    /**
     * Show Resources by tag name
     *
     * @param string $tag
     * @param array  $options
     * @return array
     */
    public function assetsByTag(string $tag, array $options = []): array
    {
        return $this->getApi()->assetsByTag($tag, $options)->getArrayCopy();
    }

    /**
     * Show Resources by moderation status
     *
     * @param string $kind
     * @param string $status
     * @param array  $options
     * @return array
     */
    public function assetsByModeration(string $kind, string $status, array $options = []): array
    {
        return $this->getApi()->assetsByModeration($kind, $status, $options)->getArrayCopy();
    }

    /**
     * Display tags list
     *
     * @param array $options
     * @return array
     * @throws ApiError
     */
    public function tags(array $options = []): array
    {
        return $this->getApi()->tags($options)->getArrayCopy();
    }

    /**
     * Display a resource
     *
     * @param string $publicId
     * @param array  $options
     * @return array
     */
    public function asset(string $publicId, array $options = []): array
    {
        return $this->getApi()->asset($publicId, $options)->getArrayCopy();
    }

    /**
     * Updates a resource
     *
     * @param string $publicId
     * @param array  $options
     * @return array
     */
    public function update(string $publicId, array $options = []): array
    {
        return $this->getApi()->update($publicId, $options)->getArrayCopy();
    }

    /**
     * List transformations
     *
     * @param array $options
     * @return array
     */
    public function transformations(array $options = []): array
    {
        return $this->getApi()->transformations($options)->getArrayCopy();
    }

    /**
     * List single transformation
     *
     * @param string $transformation
     * @param array  $options
     * @return array
     */
    public function transformation(string $transformation, array $options = []): array
    {
        return $this->getApi()->transformation($transformation, $options)->getArrayCopy();
    }

    /**
     * Delete single transformation
     *
     * @param string $transformation
     * @param array  $options
     * @return array
     * @throws ApiError
     */
    public function deleteTransformation(string $transformation, array $options = []): array
    {
        return $this->getApi()->deleteTransformation($transformation, $options)->getArrayCopy();
    }

    /**
     * Update single transformation
     *
     * @param string $transformation
     * @param array  $updates
     * @param array  $options
     * @return array
     * @throws ApiError
     */
    public function updateTransformation(string $transformation, array $updates = [], array $options = []): array
    {
        return $this->getApi()->updateTransformation($transformation, $updates, $options)->getArrayCopy();
    }

    /**
     * Create transformation
     *
     * @param string $name
     * @param string $definition
     * @param array  $options
     * @return array
     */
    public function createTransformation(string $name, string $definition, array $options = []): array
    {
        return $this->getApi()->createTransformation($name, $definition, $options)->getArrayCopy();
    }

    /**
     * List Upload Mappings
     *
     * @param array $options
     * @return array
     */
    public function uploadMappings(array $options = []): array
    {
        return $this->getApi()->uploadMappings($options)->getArrayCopy();
    }

    /**
     * Get upload mapping
     *
     * @param string $name
     * @param array  $options
     * @return array
     */
    public function uploadMapping(string $name, array $options = []): array
    {
        return $this->getApi()->uploadMapping($name, $options)->getArrayCopy();
    }

    /**
     * Create upload mapping
     *
     * @param string $name
     * @param array  $options
     * @return array
     */
    public function createUploadMapping(string $name, array $options = []): array
    {
        return $this->getApi()->createUploadMapping($name, $options)->getArrayCopy();
    }

    /**
     * Delete upload mapping
     *
     * @param string $name
     * @param array  $options
     * @return array
     * @throws ApiError
     */
    public function deleteUploadMapping(string $name, array $options = []): array
    {
        return $this->getApi()->deleteUploadMapping($name, $options)->getArrayCopy();
    }

    /**
     * Update upload mapping
     *
     * @param string $name
     * @param array  $options
     * @return array
     * @throws ApiError
     */
    public function updateUploadMapping(string $name, array $options = []): array
    {
        return $this->getApi()->updateUploadMapping($name, $options)->getArrayCopy();
    }

    /**
     * List Upload Presets
     *
     * @param array $options
     * @return array
     */
    public function uploadPresets(array $options = []): array
    {
        return $this->getApi()->uploadPresets($options)->getArrayCopy();
    }

    /**
     * Get upload mapping
     *
     * @param string $name
     * @param array  $options
     * @return array
     */
    public function uploadPreset(string $name, array $options = []): array
    {
        return $this->getApi()->uploadPreset($name, $options)->getArrayCopy();
    }

    /**
     * Create upload preset
     *
     * @param string $name
     * @param array  $options
     * @return array
     */
    public function createUploadPreset(string $name, array $options = []): array
    {
        return $this->getApi()->createUploadPreset($name, $options)->getArrayCopy();
    }

    /**
     * Delete upload preset
     *
     * @param string $name
     * @param array  $options
     * @return array
     * @throws ApiError
     */
    public function deleteUploadPreset(string $name, array $options = []): array
    {
        return $this->getApi()->deleteUploadPreset($name, $options)->getArrayCopy();
    }

    /**
     * Update upload preset
     *
     * @param string $name
     * @param array  $options
     * @return array
     * @throws ApiError
     */
    public function updateUploadPreset(string $name, array $options = []): array
    {
        return $this->getApi()->updateUploadPreset($name, $options)->getArrayCopy();
    }

    /**
     * List Root folders
     *
     * @param array $options
     * @return array
     */
    public function rootFolders(array $options = []): array
    {
        return $this->getApi()->rootFolders($options)->getArrayCopy();
    }

    /**
     * List subfolders
     *
     * @param string $name
     * @param array  $options
     * @return array
     * @throws ApiError
     */
    public function subfolders(string $name, array $options = []): array
    {
        return $this->getApi()->subfolders($name, $options)->getArrayCopy();
    }

    /**
     * Get usage details
     *
     * @param array $options
     * @return array
     * @throws ApiError
     */
    public function usage(array $options = []): array
    {
        return $this->getApi()->usage($options)->getArrayCopy();
    }

    /**
     * Ping cloudinary servers
     *
     * @return array
     */
    public function ping(): array
    {
        return $this->getApi()->ping()->getArrayCopy();
    }
}
