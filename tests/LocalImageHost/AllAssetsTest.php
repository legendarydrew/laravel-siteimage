<?php

namespace PZL\SiteImage\Tests\LocalImageHost;

use PHPUnit\Framework\Attributes\CoversClass;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\SiteImageUploadResponse;
use PZL\SiteImage\Tests\TestCase;

#[CoversClass(LocalImageHost::class)]
class AllAssetsTest extends TestCase
{

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LocalImageHost();

        for ($i = 1; $i <= 4; $i++)
        {
            $this->provider->upload($this->faker->picsum(), null, null, ['superstar']);
        }
    }

    public function testWithoutTags()
    {
        $response = $this->provider->allAssets(false);

        self::assertCount(4, $response);
        self::assertContainsOnlyInstancesOf(SiteImageUploadResponse::class, $response);
        foreach ($response as $row)
        {
            self::assertCount(0, $row->tags);
        }
    }

    public function testWithTags()
    {
        $response = $this->provider->allAssets(true);

        self::assertCount(4, $response);
        self::assertContainsOnlyInstancesOf(SiteImageUploadResponse::class, $response);
        foreach ($response as $row)
        {
            self::assertCount(1, $row->tags);
        }
    }
}
