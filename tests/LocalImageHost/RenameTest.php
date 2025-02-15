<?php
/**
 * Copyright (c) 2021 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\LocalImageHost;

use PZL\Http\ResponseCode;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * RejectTest.
 */
class RenameTest extends TestCase
{
    private LocalImageHost $provider;

    private string $source_file;
    private string $new_name;

    protected function setUp(): void
    {
        parent::setUp();
        $this->provider = new LocalImageHost();

        $image             = $this->faker->picsum($this->provider->getFolder());
        $this->source_file = basename($image);
        $this->new_name    = $this->faker->word();
        copy($image, $this->provider->getFolder() . $this->source_file);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testRename()
    {
        $response = $this->provider->rename($this->source_file, $this->new_name);
        self::assertEquals($this->new_name, $response->public_id);
        self::assertFileDoesNotExist($this->provider->getFolder() . $this->source_file);
        self::assertFileExists($this->provider->getFolder() . $this->new_name);
    }

    /**
     * @covers  \PZL\SiteImage\Host\LocalImageHost
     * @depends testRename
     */
    public function testRenameOverwriteFalse()
    {
        $image = $this->faker->picsum($this->provider->getFolder());
        copy($image, $this->provider->getFolder() . $this->new_name);

        $this->expectException(HttpException::class);
        $this->provider->rename($this->source_file, $this->new_name, FALSE);
    }

    /**
     * @covers  \PZL\SiteImage\Host\LocalImageHost
     * @depends testRename
     */
    public function testRenameOverwriteTrue()
    {
        $image = $this->faker->picsum($this->provider->getFolder());
        copy($image, $this->provider->getFolder() . $this->new_name);

        $response = $this->provider->rename($this->source_file, $this->new_name, TRUE);

        self::assertEquals($this->new_name, $response->public_id);
        self::assertFileDoesNotExist($this->provider->getFolder() . $this->source_file);
        self::assertFileExists($this->provider->getFolder() . $this->new_name);
    }

    /**
     * @covers  \PZL\SiteImage\Host\LocalImageHost
     * @depends testRename
     */
    public function testRenameInvalid()
    {
        $this->expectException(HttpException::class);
        $this->provider->rename(ResponseCode::RESPONSE_NOT_FOUND, $this->new_name);
    }
}
