<?php

namespace PZL\SiteImage\Tests\LocalImageHost;


use Illuminate\Foundation\Testing\WithFaker;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\Tests\TestCase;

class TaggedTest extends TestCase
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
    }

    /**
     * @covers \PZL\SiteImage\Host\LocalImageHost
     */
    public function testOnlyTaggedImages()
    {
        $untagged = array_map(function () {
            return $this->provider->upload($this->faker->picsum())->public_id;
        }, range(1, $this->faker->numberBetween(1, 5)));
        $tagged_one = array_map(function () {
            return $this->provider->upload($this->faker->picsum(), null, null, ['one'])->public_id;
        }, range(1, $this->faker->numberBetween(1, 5)));
        $tagged_two = array_map(function () {
            return $this->provider->upload($this->faker->picsum(), null, null, ['two'])->public_id;
        }, range(1, $this->faker->numberBetween(1, 5)));

        $results = $this->provider->tagged('one');

        foreach ($untagged as $public_id) {
            self::assertNotContains($public_id, $results);
        }

        foreach ($tagged_one as $public_id) {
            self::assertContains($public_id, $results);
        }

        foreach ($tagged_two as $public_id) {
            self::assertNotContains($public_id, $results);
        }
    }

}
