<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\LocalImageHost;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\WithFaker;
use PZL\Http\ResponseCode;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\SiteImageFormat;
use PZL\SiteImage\Tests\TestCase;
use ReflectionException;

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
    private $image;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LocalImageHost();
        $fs             = new Filesystem();
        $fs->cleanDirectory($this->provider->getFolder());

        $this->image = $this->faker->image($this->provider->getFolder());
    }

    public function testWithoutTransformation()
    {
        $public_id = basename($this->image);

        $url = $this->provider->get($public_id);
        self::assertIsURL($url);
    }

    public function testWithNewTransformation()
    {
        $public_id = basename($this->image);
        $url       = $this->provider->get($public_id, 'thumbnail');

        self::assertIsURL($url);
    }

    public function testWithExistingTransformation()
    {
        @mkdir($this->provider->getFolder() . 'thumbnail');
        $this->faker->image($this->provider->getFolder() . 'thumbnail');
        $public_id = basename($this->image);
        $url       = $this->provider->get($public_id, 'thumbnail');

        self::assertIsURL($url);
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
        }
    }

    public function testInvalidImageWithJPGPlaceholder()
    {
        @mkdir(public_path('img/ph'), 0755, TRUE);
        copy(__DIR__ . '/../../resources/assets/placeholder.jpg', public_path('img/ph/placeholder.jpg'));
        $url = $this->provider->get(ResponseCode::RESPONSE_NOT_FOUND);
        self::assertIsURL($url);
        self::assertEquals(asset('img/ph/placeholder.jpg'), $url);
        unlink(public_path('img/ph/placeholder.jpg'));
    }

    public function testInvalidImageWithPNGPlaceholder()
    {
        @mkdir(public_path('img/ph'), 0755, TRUE);
        copy(__DIR__ . '/../../resources/assets/placeholder.png', public_path('img/ph/placeholder.png'));
        $url = $this->provider->get(ResponseCode::RESPONSE_NOT_FOUND);
        self::assertIsURL($url);
        self::assertEquals(asset('img/ph/placeholder.png'), $url);
        unlink(public_path('img/ph/placeholder.png'));
    }

    public function testInvalidImageNoPlaceholder()
    {
        $url = $this->provider->get(ResponseCode::RESPONSE_NOT_FOUND);
        self::assertEmpty($url);
    }
}
