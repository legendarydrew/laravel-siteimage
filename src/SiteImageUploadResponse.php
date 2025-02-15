<?php

namespace PZL\SiteImage;

/**
 * For the benefit of the LocalImageHost, this class is used to mimic the response
 * from the Cloudinary API after uploading an image.
 */
class SiteImageUploadResponse
{
    /**
     * @var string the asset ID of the asset.
     */
    public string $asset_id;

    /**
     * @var string the public ID of the asset.
     */
    public string $public_id;

    /**
     * @var int the asset's version number.
     */
    public int $version;

    /**
     * @var string the asset's version ID, a hexadecimal code.
     */
    public string $version_id;

    /**
     * @var string the asset's signature.
     */
    public string $signature;

    /**
     * @var int the width of the asset in pixels.
     */
    public int $width;

    /**
     * @var int the height of the asset in pixels.
     */
    public int $height;

    /**
     * @var string the asset's format, as a file extension without the dot.
     */
    public string $format;

    /**
     * @var string the type of uploaded asset.
     */
    public string $resource_type;

    /**
     * @var string a timestamp of the asset's creation date.
     */
    public string $created_at;

    /**
     * @var string[] a list of associated tags.
     */
    public array $tags;

    /**
     * @var int the size of the asset in bytes.
     */
    public int $bytes;

    /**
     * @var string the type of asset.
     */
    public string $type;

    /**
     * @var string the asset's eTag, as a hexadecimal code.
     */
    public string $etag;

    /**
     * @var bool whether this asset is a placeholder asset.
     */
    public bool $placeholder;

    /**
     * @var string the HTTP URL to this asset.
     */
    public string $url;

    /**
     * @var string the HTTPS URL to this asset.
     */
    public $secure_url;

    /**
     * @var bool whether this asset has overwritten an existing asset.
     */
    public bool $overwritten;

    /**
     * @var string the original asset filename.
     */
    public string $original_filename;

    /**
     * @var string the API key associated with this asset.
     */
    public string $api_key;

    public function __construct(array $props = [])
    {
        foreach ($props as $key => $value)
        {
            if (property_exists($this, $key))
            {
                $this->$key = $value;
            }
        }
    }

    public static function fromCloudinaryWrapper(CloudinaryWrapper $cloudinaryWrapper): SiteImageUploadResponse
    {
        $result = $cloudinaryWrapper->getResult()->getArrayCopy();
        return new SiteImageUploadResponse($result);
    }

}
