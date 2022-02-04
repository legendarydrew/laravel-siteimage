<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\CloudinaryImageHost;

use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use PZL\SiteImage\CloudinaryWrapper;
use PZL\SiteImage\Host\CloudinaryImageHost;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;

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

        $this->provider        = Mockery::mock(CloudinaryImageHost::class)->makePartial();
        $this->placeholder_url = $this->faker->imageUrl;

        $this->provider->shouldReceive('getPlaceholder')->andReturn($this->placeholder_url);
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
}
