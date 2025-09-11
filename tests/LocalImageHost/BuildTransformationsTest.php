<?php

namespace PZL\SiteImage\Tests\LocalImageHost;


use Mockery;
use Mockery\Mock;
use PHPUnit\Framework\Attributes\CoversClass;
use PZL\SiteImage\Facades\SiteImageFacade;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;

#[CoversClass(LocalImageHost::class)]
class BuildTransformationsTest extends TestCase
{

    public function testDoesNothing()
    {
        // Nothing should happen.
        $provider = Mockery::mock(new LocalImageHost());
        $provider->shouldReceive('destroy')->never();
        $provider->shouldReceive('destroyAll')->never();
        $provider->shouldReceive('get')->never();
        $provider->shouldReceive('getFolder')->never();
        $provider->shouldReceive('upload')->never();

        $provider->buildTransformations();
    }

}
