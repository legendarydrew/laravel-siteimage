<?php

namespace PZL\SiteImage\Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\WithFaker;
use Orchestra\Testbench\TestCase as BaseTestCase;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class TestCase.
 *
 * @method setUpMocksBadges()
 */
abstract class TestCase extends BaseTestCase
{
    use WithFaker;

    /**
     * Define environment setup.
     *
     * @param \Illuminate\Foundation\Application $app
     * @return void
     */
    protected function defineEnvironment($app)
    {
        // Setup default database to use sqlite :memory:
        $app['config']->set('site-images.local', [
            'folder' => 'test!'
        ]);
        $app['config']->set('site-images.transformations', [
            'thumbnail' => [
                'width'         => 100,
                'height'        => 100,
                'crop'          => 'thumb',
                'gravity'       => 'face:center',
                'default_image' => 'placeholder.png'
            ]
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
            'Intervention\Image\ImageServiceProvider',
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
