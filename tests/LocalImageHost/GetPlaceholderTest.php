<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\LocalImageHost;

use Illuminate\Foundation\Testing\WithFaker;
use PZL\Http\ResponseCode;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * GetPlaceholderTest
 *
 * @package PZL\SiteImage\Tests\LocalImageHost
 */
class GetPlaceholderTest extends TestCase
{
    use WithFaker;

    /**
     * @var LocalImageHost
     */
    private $provider;

    /**
     * @var string
     */
    private $image;

    /**
     * @var string
     */
    private $placeholder_image;

    /**
     * @var string
     */
    private $placeholder_url;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LocalImageHost();
        $this->image    = $this->faker->image($this->provider->getFolder());

        // Copy one of our placeholder images to the public folder.
        $ph = config('site-images.default_image');
        chdir(public_path());
        @mkdir(dirname($ph), 0x644, true);
        copy(__DIR__ . '/../../resources/assets/placeholder.png', public_path($ph));

        $this->placeholder_image = public_path($ph);
        $this->placeholder_url   = asset($ph);
    }

    public function testPlaceholderExists()
    {
        $url = $this->provider->getPlaceholder();

        self::assertIsURL($url);
        self::assertEquals($this->placeholder_url, $url);
    }

    public function testPlaceholderWithTransformation()
    {
        $url = $this->provider->getPlaceholder('thumbnail');

        self::assertIsURL($url);
    }

    public function testWithInvalidTransformation()
    {
        $this->expectException(HttpException::class);

        $this->provider->getPlaceholder(ResponseCode::RESPONSE_NOT_FOUND);
    }

    public function testPlaceholderDoesNotExist()
    {
        @unlink($this->placeholder_image);

        $url = $this->provider->get();
        self::assertEmpty($url);
    }
}
