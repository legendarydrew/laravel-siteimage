<?php
namespace PZL\SiteImage\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * SiteImageFacade
 */
class SiteImageFacade extends Facade {

    protected static function getFacadeAccessor(): string
    {
        return 'pzl.site-image.host';
    }
}
