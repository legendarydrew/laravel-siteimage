<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\LocalHost;

use Illuminate\Filesystem\Filesystem;
use PZL\SiteImage\Host\LocalHost;
use PZL\SiteImage\Tests\TestCase;

/**
 * UploadTest.
 */
class UploadTest extends TestCase
{
    /**
     * @var LocalHost()
     */
    private $provider;

    /**
     * @var string
     */
    private $image;

    /**
     * @var string
     */
    private $filename;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LocalHost();

        $fs = new Filesystem();
        $fs->cleanDirectory($this->provider->getFolder());

        $this->image = $this->faker->image;
        $this->filename = basename($this->image);
    }

    public function testFileToRootFolder()
    {
        $public_id = $this->provider->upload($this->image);

        self::assertEquals($this->filename, $public_id);
        self::assertFileExists($this->provider->getFolder() . $this->filename);
    }

    public function testFileToChildFolder()
    {
        $dir = $this->faker->firstName;
        $public_id = $this->provider->upload($this->image, $dir);
        $target_filename = sprintf('%s--%s', strtolower($dir), strtolower($this->filename));
        self::assertEquals($target_filename, $public_id);
        self::assertFileExists($this->provider->getFolder() . $target_filename);
    }

    public function testFileWithTags()
    {
        $tag = $this->faker->word;
        $public_id = $this->provider->upload($this->image, null, null, [$tag]);
        $tagged_images = $this->provider->tagged($tag);
        self::assertContains($public_id, $tagged_images);
    }

    public function testExistingFile()
    {
        $old_public_id = $this->provider->upload($this->image);
        $new_public_id = $this->provider->upload($this->image);

        self::assertNotEquals($new_public_id, $old_public_id);
        self::assertFileExists(sprintf('%s/%s', $this->provider->getFolder(), $old_public_id));
        self::assertFileExists(sprintf('%s/%s', $this->provider->getFolder(), $new_public_id));
    }

    public function testUrl()
    {
        $this->image = $this->faker->imageUrl();
        $public_id = $this->provider->upload($this->image);

        self::assertFileExists($this->provider->getFolder() . $public_id);
    }

    public function testBase64()
    {
        $data = base64_encode(file_get_contents($this->image));
        $public_id = $this->provider->upload($data, null, $this->filename);

        self::assertEquals($this->filename, $public_id);
        self::assertFileExists($this->provider->getFolder() . $this->filename);
    }

    public function testEagerTransformations()
    {
        $transformations = ['thumbnail'];
        $public_id = $this->provider->upload($this->image, null, null, [], $transformations);

        foreach ($transformations as $transformation) {
            self::assertFileExists(sprintf('%s/%s/%s', $this->provider->getFolder(), $transformation, $public_id));
        }
    }
}
