<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\LocalImageHost;

use Illuminate\Foundation\Testing\WithFaker;
use PZL\Http\ResponseCode;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\SiteImageFormat;
use PZL\SiteImage\Tests\TestCase;
use ReflectionException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * GetTest.
 */
class GetTest extends TestCase
{
    use WithFaker;

    /**
     * @var LocalImageHost
     */
    private $provider;

    /**
     * @var string
     */
    private $placeholder_image;

    /**
     * @var string
     */
    private $placeholder_url;

    /**
     * @var string
     */
    private $public_id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LocalImageHost();
        $image          = $this->faker->image($this->provider->getFolder());

        // Copy one of our placeholder images to the public folder.
        $ph = config('site-images.default_image');
        chdir(public_path());
        @mkdir(dirname($ph), 0x644, TRUE);
        copy(__DIR__ . '/../../resources/assets/placeholder.png', public_path($ph));

        $this->public_id         = basename($image);
        $this->placeholder_image = public_path($ph);
        $this->placeholder_url   = asset($ph);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testWithoutPublicID()
    {
        $url = $this->provider->get();

        self::assertIsURL($url);
        self::assertEquals($this->placeholder_url, $url);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testWithNullPublicID()
    {
        $url = $this->provider->get(null);

        self::assertIsURL($url);
        self::assertEquals($this->placeholder_url, $url);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testWithoutTransformation()
    {
        $url = $this->provider->get($this->public_id);
        self::assertIsURL($url);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testWithNewTransformation()
    {
        $url = $this->provider->get($this->public_id, 'thumbnail');

        self::assertIsURL($url);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testWithExistingTransformation()
    {
        @mkdir($this->provider->getFolder() . 'thumbnail');
        $this->faker->image($this->provider->getFolder() . 'thumbnail');
        $url = $this->provider->get($this->public_id, 'thumbnail');

        self::assertIsURL($url);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testWithInvalidTransformation()
    {
        $this->expectException(HttpException::class);

        @mkdir($this->provider->getFolder() . 'thumbnail');
        $this->faker->image($this->provider->getFolder() . 'thumbnail');
        $this->provider->get($this->public_id, ResponseCode::RESPONSE_NOT_FOUND);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testWithTransformationWithoutWidth()
    {
        @mkdir($this->provider->getFolder() . 'without-width');
        $this->faker->image($this->provider->getFolder() . 'without-width');
        $url = $this->provider->get($this->public_id, 'without-width');

        self::assertIsURL($url);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testWithTransformationWithoutHeight()
    {
        @mkdir($this->provider->getFolder() . 'without-height');
        $this->faker->image($this->provider->getFolder() . 'without-height');
        $url = $this->provider->get($this->public_id, 'without-height');

        self::assertIsURL($url);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testWithTransformationWithoutDimensions()
    {
        @mkdir($this->provider->getFolder() . 'without-both');
        $this->faker->image($this->provider->getFolder() . 'without-both');
        $url = $this->provider->get($this->public_id, 'without-both');

        self::assertIsURL($url);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     * @throws ReflectionException
     */
    public function testAsFormat()
    {
        $formats = SiteImageFormat::values();

        foreach ($formats as $format)
        {
            $url = $this->provider->get($this->public_id, null, $format);
            self::assertIsURL($url);
        }
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testNoImageWithPlaceholder()
    {
        $url = $this->provider->get(null);
        self::assertIsURL($url);
        self::assertEquals($this->placeholder_url, $url);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testInvalidImageWithPlaceholder()
    {
        $url = $this->provider->get(ResponseCode::RESPONSE_NOT_FOUND);
        self::assertIsURL($url);
        self::assertEquals($this->placeholder_url, $url);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testInvalidImageNoPlaceholder()
    {
        @unlink($this->placeholder_image);
        $url = $this->provider->get(ResponseCode::RESPONSE_NOT_FOUND);
        self::assertEmpty($url);
    }
}
