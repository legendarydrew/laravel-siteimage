<?php

namespace PZL\SiteImage\Tests\LocalImageHost;


use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\WithFaker;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;

class ApproveTest extends TestCase
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

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testApprovesImage()
    {
        $public_id = basename($this->image);
        $this->provider->approve($public_id);

        // Nothing should happen.

        $url = $this->provider->get($public_id);
        self::assertIsURL($url);
        self::assertNotEquals(asset('img/ph/placeholder.jpg'), $url);
        self::assertNotEquals(asset('img/ph/placeholder.png'), $url);
    }

}
