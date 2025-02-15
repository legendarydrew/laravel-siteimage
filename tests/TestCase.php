<?php

namespace PZL\SiteImage\Tests;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\WithFaker;
use Intervention\Image\ImageServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PZL\SiteImage\Host\LocalImageHost;
use PZL\SiteImage\SiteImageServiceProvider;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class TestCase.
 *
 * @method setUpMocksBadges()
 */
abstract class TestCase extends BaseTestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Set up image providers for Faker.
        $this->faker->addProvider(new \Mmo\Faker\PicsumProvider($this->faker));
        $this->faker->addProvider(new \Mmo\Faker\LoremSpaceProvider($this->faker));
        $this->faker->addProvider(new \Mmo\Faker\FakeimgProvider($this->faker));

        // Remove any existing test images.
        $fs       = new Filesystem();
        $provider = new LocalImageHost();
        $fs->cleanDirectory($provider->getFolder());
    }

    /**
     * Define environment setup.
     *
     * @param Application $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
        $app['config']->set('site-images', [
            'local'           => [
                'folder' => 'test!'
            ],
            'cloudinary'      => [
                'cloudName' => 'test!',
                'apiKey'    => 'damn',
                'apiSecret' => 'what-the',
                'scaling'   => []
            ],
            'transformations' => [
                'thumbnail'      => [
                    'width'         => 100,
                    'height'        => 100,
                    'crop'          => 'thumb',
                    'gravity'       => 'face:center',
                    'default_image' => 'img/placeholder.png'
                ],
                'without-width'  => [
                    'height'        => 100,
                    'crop'          => 'thumb',
                    'gravity'       => 'face:center',
                    'default_image' => 'img/placeholder.png'
                ],
                'without-height' => [
                    'width'         => 100,
                    'crop'          => 'thumb',
                    'gravity'       => 'face:center',
                    'default_image' => 'img/placeholder.png'
                ],
                'without-both'   => [
                    'gravity'       => 'face:center',
                    'default_image' => 'img/placeholder.png'
                ]
            ],
            'default_image'   => 'img/placeholder.png'
        ]);
    }

    /**
     * Get package providers.
     *
     * @param Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            SiteImageServiceProvider::class,
            ImageServiceProvider::class
        ];
    }

    /**
     * assertIsURL().
     *
     * @param string $expectedAsURL
     */
    public function assertIsURL(string $expectedAsURL)
    {
        // https://stackoverflow.com/a/10002262/4073160
        $pattern =
            '(?xi)\b((?:https?://|www\d{0,3}[.]|[a-z0-9.\-]+[.][a-z]{2,4}/)(?:[^\s()<>]+|\(([^\s()<>]+|(\([^\s()<>]+\)))*\))+(?:\(([^\s()<>]+|(\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))';
        $this->assertEquals(1, preg_match("#$pattern#i", $expectedAsURL), sprintf('Expected a URL string but got %s.', $expectedAsURL));
    }

    /**
     * assertResponseCode()
     * Passes if a specific HTTP exception was thrown.
     *
     * @param int      $response_code
     * @param callable $closure
     */
    public function assertResponseCode(int $response_code, callable $closure)
    {
        try
        {
            $closure();
        }
        catch (HttpException $exception)
        {
            $actual_code = $exception->getStatusCode();
            $message     = $exception->getMessage();
            self::assertEquals(
                $response_code,
                $actual_code,
                sprintf(
                    "Expected HTTP response code %u but got %u:\n%s",
                    $response_code,
                    $actual_code,
                    $message
                )
            );

            return;
        }

        self::fail('An HttpException should have been thrown.');
    }

    /**
     * assertTruthy().
     * Passes if the value is equivalent to TRUE.
     *
     * @param        $actual
     * @param string $message
     */
    public function assertTruthy($actual, string $message = '')
    {
        $actual = filter_var($actual, FILTER_VALIDATE_BOOLEAN);
        self::assertTrue($actual, $message);
    }

    /**
     * assertFalsy().
     * Passes if the value is equivalent to FALSE.
     *
     * @param        $actual
     * @param string $message
     */
    public function assertFalsy($actual, string $message = '')
    {
        $actual = filter_var($actual, FILTER_VALIDATE_BOOLEAN);
        self::assertFalse($actual, $message);
    }
}
