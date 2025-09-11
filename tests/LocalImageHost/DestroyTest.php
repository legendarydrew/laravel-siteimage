<?php

namespace PZL\SiteImage\Tests\LocalImageHost;


use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;

#[CoversClass(LocalImageHost::class)]
class DestroyTest extends TestCase
{
    private LocalImageHost $provider;

    private string $image;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LocalImageHost();
        $this->image = $this->faker->picsum();

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
