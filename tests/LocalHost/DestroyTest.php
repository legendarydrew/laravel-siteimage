<?php

namespace PZL\SiteImage\Tests\LocalHost;


use Illuminate\Filesystem\Filesystem;
use PZL\SiteImage\Host\LocalHost;
use PZL\SiteImage\Tests\TestCase;

class DestroyTest extends TestCase
{
    /**
     * @var LocalHost
     */
    private $provider;

    private $image;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LocalHost();
        $this->image = $this->faker->image;

        $fs = new Filesystem();
        $fs->cleanDirectory($this->provider->getFolder());
    }

    public function testDestroy()
    {
        $public_id = basename($this->image);
        $this->provider->destroy($public_id);

        self::assertFileDoesNotExist($this->provider->getFolder() . $public_id);
    }
}
