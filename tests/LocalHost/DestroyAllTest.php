<?php
/**
 * Copyright (c) 2022 Perfect Zero Labs.
 */

namespace PZL\SiteImage\Tests\LocalHost;

use Illuminate\Filesystem\Filesystem;
use Intervention\Image\Facades\Image;
use PZL\SiteImage\Host\LocalHost;
use PZL\SiteImage\Tests\TestCase;

/**
 * DestroyAllTest.
 */
class DestroyAllTest extends TestCase
{
    /**
     * @var LocalHost
     */
    private $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->provider = new LocalHost();
        $fs = new Filesystem();
        $fs->cleanDirectory($this->provider->getFolder());
    }

    public function testAll()
    {
        $image_count = $this->faker->numberBetween(1, 5);
        $public_ids = array_map(function () {
            return $this->provider->upload($this->faker->image);
        }, range(1, $image_count));

        $this->provider->destroyAll();

        foreach ($public_ids as $public_id) {
            self::assertFileDoesNotExist($this->provider->getFolder() . $public_id);
        }
    }

    public function testTag()
    {
        $untagged = array_map(function () {
            return $this->provider->upload($this->faker->image);
        }, range(1, $this->faker->numberBetween(1, 5)));
        $tagged_one = array_map(function () {
            return $this->provider->upload($this->faker->image, null, null, ['one']);
        }, range(1, $this->faker->numberBetween(1, 5)));
        $tagged_two = array_map(function () {
            return $this->provider->upload($this->faker->image, null, null, ['two']);
        }, range(1, $this->faker->numberBetween(1, 5)));

        $this->provider->destroyAll('one');

        foreach ($untagged as $public_id) {
            self::assertFileExists($this->provider->getFolder() . $public_id);
        }

        foreach ($tagged_one as $public_id) {
            self::assertFileDoesNotExist($this->provider->getFolder() . $public_id);
        }

        foreach ($tagged_two as $public_id) {
            self::assertFileExists($this->provider->getFolder() . $public_id);
        }
    }
}
