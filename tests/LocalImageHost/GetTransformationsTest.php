<?php

namespace PZL\SiteImage\Tests\LocalImageHost;

use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;

class GetTransformationsTest extends TestCase
{

    public function testReturnsTransformations()
    {
        $provider = new LocalImageHost();

        self::assertEquals(config('site-images.transformations'), $provider->getTransformations());
    }

}
