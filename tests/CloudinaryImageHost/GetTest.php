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
    private $image;

    /**
     * @var string
     */
    private $placeholder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = Mockery::mock(CloudinaryImageHost::class)->makePartial();
        $this->wrapper = Mockery::mock(CloudinaryWrapper::class);
        $this->image = $this->faker->imageUrl;
        $this->placeholder = $this->faker->imageUrl;

        $this->provider->shouldReceive('getCloudinaryWrapper')->andReturn($this->wrapper);
        $this->wrapper->shouldReceive('show')->andReturnUsing(function($arg)
        {
            return $arg === (string)ResponseCode::RESPONSE_NOT_FOUND ? $this->placeholder : $this->image;
        });
    }

    public function testWithoutTransformation()
    {
        $public_id = basename($this->image);
        $url = $this->provider->get($public_id);

        self::assertIsURL($url);
        self::assertEquals($this->image, $url);
    }

    public function testWithTransformation()
    {
        $public_id = basename($this->image);
        $url       = $this->provider->get($public_id, 'thumbnail');

        self::assertIsURL($url);
        self::assertEquals($this->image, $url);
    }

    /**
     * @throws ReflectionException
     */
    public function testAsFormat()
    {
        $formats = SiteImageFormat::values();

        foreach ($formats as $format)
        {
            $public_id = basename($this->image);
            $url       = $this->provider->get($public_id, null, $format);

            self::assertIsURL($url);
            self::assertEquals($this->image, $url);
        }
    }

    public function testInvalidImage()
    {
        $url = $this->provider->get(ResponseCode::RESPONSE_NOT_FOUND);
        self::assertIsURL($url);
        self::assertEquals($this->placeholder, $url);
    }
}
