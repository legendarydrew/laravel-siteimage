# Site Image package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/legendarydrew/laravel-siteimage.svg?style=flat-square)](https://packagist.org/packages/legendarydrew/laravel-siteimage)
[![Total Downloads](https://img.shields.io/packagist/dt/legendarydrew/laravel-siteimage.svg?style=flat-square)](https://packagist.org/packages/legendarydrew/laravel-siteimage)

A package for managing cloud-hosted images in a Laravel project.

This was developed to allow local mimicking of Cloudinary-hosted images, instead of using precious (and probably expensive) server bandwidth during development.

## Installation

You can install the package via composer:

```bash
composer require legendarydrew/laravel-siteimage
```

**Copy configuration file**
```php
php artisan vendor:publish --provider="PZL\SiteImage\SiteImageServiceProvider" --tag="config"
```

**Copy placeholder images (for missing images)**
```php
php artisan vendor:publish --provider="PZL\SiteImage\SiteImageServiceProvider" --tag="assets"
```
(or create your own!)

**Environment variables**

`SITE_IMAGE_PROVIDER`

LocalImage (default) or CloudinaryImage.

`SITE_IMAGE_LOCAL_FOLDER`

The folder where locally-hosted images are stored, relative to the `public` folder (default 'img').

## Usage

```php
SiteImage::get($image_id[, $transformation, $format])
SiteImage::approve($image_id)
SiteImage::reject($image_id)
SiteImage::destroy($image_id)
SiteImage::rename($old_image_id, $new_image_id)
...
```

### Testing

```bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email drew@pzlabs.co instead of using the issue tracker.

## Credits

-   [Drew Maughan](https://github.com/legendarydrew)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Laravel Package Boilerplate

This package was generated using the [Laravel Package Boilerplate](https://laravelpackageboilerplate.com).
