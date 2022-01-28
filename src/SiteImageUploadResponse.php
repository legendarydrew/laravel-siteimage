<?php

namespace PZL\SiteImage;

/**
 * For the benefit of the LocalImageHost, this class is used to mimic the response
 * from the Cloudinary API after uploading an image.
 */
class SiteImageUploadResponse
{
    public $public_id;     // string
    public $version;       // technically a long integer, but can be a string.
    public $signature;     // string
    public $width;         // integer
    public $height;        // integer
    public $format;        // string
    public $resource_type; // "image"
    public $created_at;    // string (timestamp)
    public $bytes;         // integer
    public $type;          // "upload"
    public $url;           // string (http URL)
    public $secure_url;    // string (https URL)

    public static function fromCloudinaryWrapper(CloudinaryWrapper $wrapper): SiteImageUploadResponse
    {
        $result = $wrapper->getResult();
        $response = new SiteImageUploadResponse();

        foreach ($result as $key => $value) {
            if ( property_exists($response, $key) ) {
                $result->$key = $value;
            }
        }

        return $response;
    }
}