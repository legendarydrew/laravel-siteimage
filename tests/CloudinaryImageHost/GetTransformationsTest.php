<?php

namespace PZL\SiteImage\Tests\CloudinaryImageHost;


use PZL\SiteImage\Host\CloudinaryImageHost;
use PZL\SiteImage\Tests\TestCase;

class GetTransformationsTest extends TestCase
{

    public function testReturnsTransformations()
    {
        $provider = new CloudinaryImageHost();

        self::assertEquals(config('site-images.transformations'), $provider->getTransformations());
    }

}
