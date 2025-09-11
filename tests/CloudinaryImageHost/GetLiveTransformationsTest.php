<?php

namespace PZL\SiteImage\Tests\CloudinaryImageHost;


use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\ApiResponse;
use Mockery;
use PZL\SiteImage\CloudinaryWrapper;
use PZL\SiteImage\Host\CloudinaryImageHost;
use PZL\SiteImage\Tests\TestCase;

class GetLiveTransformationsTest extends TestCase
{

    public function testReturnsTransformations()
    {
        $api      = Mockery::mock(AdminApi::class);
        $wrapper  = Mockery::mock(CloudinaryWrapper::class);
        $provider = Mockery::mock(CloudinaryImageHost::class)->makePartial();

        $wrapper->shouldReceive('getApi')->andReturn($api);
        $provider->shouldReceive('getCloudinaryWrapper')->andReturn($wrapper);

        $api->shouldReceive('transformations')->andReturn(new ApiResponse([
            'transformations' => [
                ['name' => 'tom'],
                ['name' => 'richard'],
                ['name' => 'harry'],
            ],
        ], []));

        self::assertEquals(['tom', 'richard', 'harry'], $provider->getLiveTransformations());
    }

}
