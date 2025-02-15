# Changelog

All notable changes to `laravel-siteimage` will be documented in this file.

## 1.0.12 - 2025-02-15
- consistently return a SiteImageUploadResponse when renaming images.

## 1.0.11 - 2024-12-25
- ability to overwrite files with upload() for LocalImageHost.

## 1.0.11 - 2024-12-13
- added rename().

## 1.0.10 - 2022-04-05
- added support for configurable default (placeholder) images.

## 1.0.9 - 2022-02-04
- fixed inconsistencies with upload().

## 1.0.8 - 2022-02-03
- added allAssets() to both image hosts.

## 1.0.7 - 2022-01-28
- support for LocalImageHost transformations without a width and/or height defined.
- test suites now remove any locally-hosted images when each test starts.

## 1.0.6 - 2022-01-28
- more information than just the image filename is returned when uploading images.

## 1.0.5 - 2022-01-27
- completed test suites for LocalImageHost.

## 1.0.4 - 2022-01-27
- renamed host classes.
- SiteImage::get() can be called with a null image ID.

## 1.0.3 - 2022-01-26
- added test suites for both the LocalHost and Cloudinary host, though some are not yet populated.
- added a SiteImage facade.

## 1.0.1 - 2022-01-24
- removed unnecessary dependency.
 
## 1.0.0 - 2022-01-24
- initial release.
