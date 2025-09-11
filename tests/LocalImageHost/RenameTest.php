<?php
/**
 * Copyright (c) 2021 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\LocalImageHost;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PZL\Http\ResponseCode;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[CoversClass(LocalImageHost::class)]
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

    public function testRename()
    {
        $response = $this->provider->rename($this->source_file, $this->new_name);
        self::assertEquals($this->new_name, $response->public_id);
        self::assertFileDoesNotExist($this->provider->getFolder() . $this->source_file);
        self::assertFileExists($this->provider->getFolder() . $this->new_name);
    }

    #[Depends('testRename')]
    public function testRenameOverwriteFalse()
    {
        $image = $this->faker->picsum($this->provider->getFolder());
        copy($image, $this->provider->getFolder() . $this->new_name);

        $this->expectException(HttpException::class);
        $this->provider->rename($this->source_file, $this->new_name, false);
    }

    #[Depends('testRename')]
    public function testRenameOverwriteTrue()
    {
        $image = $this->faker->picsum($this->provider->getFolder());
        copy($image, $this->provider->getFolder() . $this->new_name);

        $response = $this->provider->rename($this->source_file, $this->new_name, true);

        self::assertEquals($this->new_name, $response->public_id);
        self::assertFileDoesNotExist($this->provider->getFolder() . $this->source_file);
        self::assertFileExists($this->provider->getFolder() . $this->new_name);
    }

    #[Depends('testRename')]
    public function testRenameInvalid()
    {
        $this->expectException(HttpException::class);
        $this->provider->rename(ResponseCode::RESPONSE_NOT_FOUND, $this->new_name);
    }
}
