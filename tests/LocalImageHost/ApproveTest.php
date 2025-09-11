<?php

namespace PZL\SiteImage\Tests\LocalImageHost;


use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\WithFaker;
use PHPUnit\Framework\Attributes\CoversClass;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;

#[CoversClass(LocalImageHost::class)]
class ApproveTest extends TestCase
{
    use WithFaker;

    private LocalImageHost $provider;

    private string $image;


    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LocalImageHost();
        $fs             = new Filesystem();
        $fs->cleanDirectory($this->provider->getFolder());

        $this->image = $this->faker->picsum($this->provider->getFolder());
    }

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
