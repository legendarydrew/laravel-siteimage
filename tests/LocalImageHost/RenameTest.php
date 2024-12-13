<?php
/**
 * Copyright (c) 2021 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\LocalImageHost;

use PZL\Http\ResponseCode;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;

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

        $image             = $this->faker->image($this->provider->getFolder());
        $this->source_file = basename($image);
        $this->new_name    = $this->faker->word();
        copy($image, $this->provider->getFolder() . $this->source_file);

    }

    public function testRename()
    {
        $response = $this->provider->rename($this->source_file, $this->new_name);
        self::assertEquals($this->new_name, $response['public_id']);
        self::assertFileDoesNotExist($this->provider->getFolder() . $this->source_file);
        self::assertFileExists($this->provider->getFolder() . $this->new_name);
    }

    /**
     * @depends testRename
     */
    public function testRenameOverwriteFalse()
    {
        $image             = $this->faker->image($this->provider->getFolder());
        copy($image, $this->provider->getFolder() . $this->new_name);

        $this->expectExceptionCode(ResponseCode::RESPONSE_PRECONDITION_FAILED);
        $this->provider->rename($this->source_file, $this->new_name, false);
    }

    /**
     * @depends testRename
     */
    public function testRenameOverwriteTrue()
    {
        $image             = $this->faker->image($this->provider->getFolder());
        copy($image, $this->provider->getFolder() . $this->new_name);

        $this->provider->rename($this->source_file, $this->new_name, TRUE);
    }

    /**
     * @depends testRename
     */
    public function testRenameInvalid()
    {
        $this->expectException((string)ResponseCode::RESPONSE_NOT_FOUND);
        $this->provider->rename(ResponseCode::RESPONSE_NOT_FOUND, $this->new_name);
    }
}
