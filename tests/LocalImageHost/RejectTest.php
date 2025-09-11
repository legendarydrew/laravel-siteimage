<?php
/**
 * Copyright (c) 2021 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\LocalImageHost;

use PHPUnit\Framework\Attributes\CoversClass;
use PZL\Http\ResponseCode;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\SiteImageHost;
use PZL\SiteImage\Tests\TestCase;

#[CoversClass(LocalImageHost::class)]
class RejectTest extends TestCase
{
    /**
     * @var LocalImageHost
     */
    private SiteImageHost $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new LocalImageHost();
    }

    public function testExisting()
    {
        $image = $this->faker->picsum($this->provider->getFolder());
        $filename = basename($image);
        copy($image, $this->provider->getFolder() . $filename);

        self::assertFileExists($this->provider->getFolder() . $filename);

        $this->provider->reject($filename);

        self::assertFileDoesNotExist($this->provider->getFolder() . $filename);
    }

    public function testInvalid()
    {
        @unlink($this->provider->getFolder() . ResponseCode::RESPONSE_NOT_FOUND);
        self::assertFileDoesNotExist($this->provider->getFolder() . ResponseCode::RESPONSE_NOT_FOUND);

        $this->provider->reject(ResponseCode::RESPONSE_NOT_FOUND);

        self::assertFileDoesNotExist($this->provider->getFolder() . ResponseCode::RESPONSE_NOT_FOUND);
    }
}
