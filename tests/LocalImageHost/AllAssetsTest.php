<?php

namespace PZL\SiteImage\Tests\LocalImageHost;

use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\SiteImageUploadResponse;
use PZL\SiteImage\Tests\TestCase;

class AllAssetsTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LocalImageHost();

        for ($i = 1; $i <= 4; $i++) {
            $this->provider->upload( $this->faker->picsum(), null, null, ['superstar'] );
        }
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testWithoutTags()
    {
        $response = $this->provider->allAssets(false);

        self::assertCount(4, $response);
        self::assertContainsOnlyInstancesOf(SiteImageUploadResponse::class, $response);
        foreach ($response as $row) {
            self::assertCount(0, $row->tags);
        }
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testWithTags()
    {
        $response = $this->provider->allAssets(true);

        self::assertCount(4, $response);
        self::assertContainsOnlyInstancesOf(SiteImageUploadResponse::class, $response);
        foreach ($response as $row) {
            self::assertCount(1, $row->tags);
        }
    }
}
