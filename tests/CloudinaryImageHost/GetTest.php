<?php

namespace PZL\SiteImage\Tests\CloudinaryImageHost;


use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use PZL\Http\ResponseCode;
use PZL\SiteImage\CloudinaryWrapper;
use PZL\SiteImage\Host\CloudinaryImageHost;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\SiteImageFormat;
use PZL\SiteImage\Tests\TestCase;
use ReflectionException;

/**
 * GetTest
 *
 * @package PZL\SiteImage\Tests\CloudinaryImageHost
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
    private $image_url;

    /**
     * @var string
     */
    private $placeholder_url;

    /**
     * @covers \PZL\SiteImage\Host\CloudinaryImageHost
     */
    public function testWithoutPublicID()
    {
        $url = $this->provider->get();

        self::assertIsURL($url);
        self::assertEquals($this->placeholder_url, $url);
    }

    /**
     * @covers \PZL\SiteImage\Host\CloudinaryImageHost
     */
    public function testWithNullPublicID()
    {
        $url = $this->provider->get(null);

        self::assertIsURL($url);
        self::assertEquals($this->placeholder_url, $url);
    }

    /**
     * @covers \PZL\SiteImage\Host\CloudinaryImageHost
     */
    public function testWithoutTransformation()
    {
        $url = $this->provider->get($this->public_id);

        self::assertIsURL($url);
        self::assertEquals($this->image_url, $url);
    }

    /**
     * @covers \PZL\SiteImage\Host\CloudinaryImageHost
     */
    public function testWithTransformation()
    {
        $url = $this->provider->get($this->public_id, 'thumbnail');

        self::assertIsURL($url);
        self::assertEquals($this->image_url, $url);
    }

    /**
     * @covers \PZL\SiteImage\Host\CloudinaryImageHost
     * @throws ReflectionException
     */
    public function testAsFormat()
    {
        $formats = SiteImageFormat::values();

        foreach ($formats as $format)
        {
            $url = $this->provider->get($this->public_id, null, $format);

            self::assertIsURL($url);
            self::assertEquals($this->image_url, $url);
        }
    }

    /**
     * @covers \PZL\SiteImage\Host\CloudinaryImageHost
     */
    public function testInvalidImage()
    {
        $url = $this->provider->get(ResponseCode::RESPONSE_NOT_FOUND);
        self::assertIsURL($url);
        self::assertEquals($this->placeholder_url, $url);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider        = Mockery::mock(CloudinaryImageHost::class)->makePartial();
        $this->wrapper         = Mockery::mock(CloudinaryWrapper::class);
        $this->image_url       = $this->faker->picsumUrl();
        $this->placeholder_url = $this->faker->picsumUrl();
        $this->public_id       = basename($this->image_url);

        $this->provider->shouldReceive('getCloudinaryWrapper')->andReturn($this->wrapper);
        $this->wrapper->shouldReceive('show')->andReturnUsing(function ($arg)
        {
            return in_array($arg, [(string)ResponseCode::RESPONSE_NOT_FOUND, config('site-images.default_image')]) ? $this->placeholder_url : $this->image_url;
        });
    }
}
