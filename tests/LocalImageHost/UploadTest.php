<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\LocalImageHost;

use Illuminate\Filesystem\Filesystem;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;

/**
 * UploadTest.
 */
class UploadTest extends TestCase
{
    /**
     * @var LocalImageHost()
     */
    private LocalImageHost $provider;

    /**
     * @var string
     */
    private string $image;

    /**
     * @var string
     */
    private string $filename;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LocalImageHost();

        $fs = new Filesystem();
        $fs->cleanDirectory($this->provider->getFolder());

        $this->image    = $this->faker->image;
        $this->filename = basename($this->image);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testFileToRootFolder()
    {
        $public_id = $this->provider->upload($this->image)->public_id;

        self::assertEquals($this->filename, $public_id);
        self::assertFileExists($this->provider->getFolder() . $this->filename);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testFileToChildFolder()
    {
        $dir             = $this->faker->firstName;
        $public_id       = $this->provider->upload($this->image, $dir)->public_id;
        $target_filename = sprintf('%s--%s', strtolower($dir), strtolower($this->filename));
        self::assertEquals($target_filename, $public_id);
        self::assertFileExists($this->provider->getFolder() . $target_filename);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testFileWithTags()
    {
        $tag           = $this->faker->word;
        $public_id     = $this->provider->upload($this->image, null, null, [$tag])->public_id;
        $tagged_images = $this->provider->tagged($tag);
        self::assertContains($public_id, $tagged_images);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testExistingFile()
    {
        $old_public_id = $this->provider->upload($this->image)->public_id;
        $new_public_id = $this->provider->upload($this->image)->public_id;

        self::assertNotEquals($new_public_id, $old_public_id);
        self::assertFileExists(sprintf('%s/%s', $this->provider->getFolder(), $old_public_id));
        self::assertFileExists(sprintf('%s/%s', $this->provider->getFolder(), $new_public_id));
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testUrl()
    {
        $this->image = $this->faker->imageUrl();
        $public_id   = $this->provider->upload($this->image)->public_id;

        self::assertFileExists($this->provider->getFolder() . $public_id);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testBase64()
    {
        $data      = base64_encode(file_get_contents($this->image));
        $public_id = $this->provider->upload($data, null, $this->filename)->public_id;

        self::assertEquals($this->filename, $public_id);
        self::assertFileExists($this->provider->getFolder() . $this->filename);
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testEagerTransformations()
    {
        $transformations = ['thumbnail'];
        $public_id       = $this->provider->upload($this->image, null, null, [], $transformations)->public_id;

        foreach ($transformations as $transformation)
        {
            self::assertFileExists(sprintf('%s/%s/%s', $this->provider->getFolder(), $transformation, $public_id));
        }
    }
}
