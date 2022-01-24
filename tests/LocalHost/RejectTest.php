<?php
/**
 * Copyright (c) 2021 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\LocalHost;

use PZL\Http\ResponseCode;
use PZL\SiteImage\Host\LocalHost;
use PZL\SiteImage\Tests\TestCase;

/**
 * RejectTest.
 */
class RejectTest extends TestCase
{
    /**
     * @var LocalHost
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->provider = new LocalHost();
    }

    public function testExisting()
    {
        $image = $this->faker->image($this->provider->getFolder());
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