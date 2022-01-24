<?php

namespace PZL\SiteImage\Tests\CloudinaryHost;


use Illuminate\Foundation\Testing\WithFaker;
use JD\Cloudder\Facades\Cloudder;
use PZL\Http\ResponseCode;
use PZL\SiteImage\Host\CloudinaryHost;
use PZL\SiteImage\Host\LocalHost;
use PZL\SiteImage\ImageFormat;
use PZL\SiteImage\Tests\TestCase;
use ReflectionException;

class GetTest extends TestCase
{
    use WithFaker;

    /**
     * @var LocalHost
     */
    private $provider;

    /**
     * @var string
     */
    private $image;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new CloudinaryHost();
        $this->image = $this->faker->imageUrl;
        $this->placeholder = $this->faker->imageUrl;

        Cloudder::shouldReceive('show')->withArgs([$this->image])->andReturn($this->image);
        Cloudder::shouldReceive('show')->withArgs([ResponseCode::RESPONSE_NOT_FOUND])->andReturn($this->placeholder);
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
        $formats = ImageFormat::values();

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
