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
    public $asset_id;

    /**
     * @var string the public ID of the asset.
     */
    public $public_id;

    /**
     * @var int the asset's version number.
     */
    public $version;

    /**
     * @var string the asset's version ID, a hexadecimal code.
     */
    public $version_id;

    /**
     * @var string the asset's signature.
     */
    public $signature;

    /**
     * @var int the width of the asset in pixels.
     */
    public $width;

    /**
     * @var int the height of the asset in pixels.
     */
    public $height;

    /**
     * @var string the asset's format, as a file extension without the dot.
     */
    public $format;

    /**
     * @var string the type of uploaded asset.
     */
    public $resource_type;

    /**
     * @var string a timestamp of the asset's creation date.
     */
    public $created_at;

    /**
     * @var string[] a list of associated tags.
     */
    public $tags;

    /**
     * @var int the size of the asset in bytes.
     */
    public $bytes;

    /**
     * @var string the type of asset.
     */
    public $type;

    /**
     * @var string the asset's eTag, as a hexadecimal code.
     */
    public $etag;

    /**
     * @var bool whether this asset is a placeholder asset.
     */
    public $placeholder;

    /**
     * @var string the HTTP URL to this asset.
     */
    public $url;

    /**
     * @var string the HTTPS URL to this asset.
     */
    public $secure_url;

    /**
     * @var bool whether this asset has overwritten an existing asset.
     */
    public $overwritten;

    /**
     * @var string the original asset filename.
     */
    public $original_filename;

    /**
     * @var string the API key associated with this asset.
     */
    public $api_key;

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

    /**
     * @param CloudinaryWrapper $wrapper
     * @return SiteImageUploadResponse
     */
    public static function fromCloudinaryWrapper(CloudinaryWrapper $wrapper): SiteImageUploadResponse
    {
        $result = $wrapper->getResult()->getArrayCopy();
        return new SiteImageUploadResponse($result);
    }

}
