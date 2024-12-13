<?php

namespace PZL\SiteImage\Tests\CloudinaryImageHost;


use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Cloudinary;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use PZL\SiteImage\CloudinaryWrapper;
use PZL\SiteImage\Host\CloudinaryImageHost;
use PZL\SiteImage\SiteImageUploadResponse;
use PZL\SiteImage\Tests\TestCase;
use function PHPUnit\Framework\assertCount;

class AllAssetsTest extends TestCase
{
    use WithFaker;

    /**
     * @var Mockery\MockInterface
     */
    private $media;

    public function setUp(): void
    {
        parent::setUp();

        $this->provider = new CloudinaryImageHost();
        $this->provider = Mockery::mock(CloudinaryImageHost::class);
        $this->api      = Mockery::mock(AdminApi::class);
        $cloudinary     = Mockery::mock(Cloudinary::class);
        $uploader       = Mockery::mock(UploadApi::class);

        $this->cloudinary_wrapper = Mockery::mock(CloudinaryWrapper::class);
        $this->cloudinary_wrapper->shouldReceive('getApi')->andReturn($this->api);
        $this->cloudinary_wrapper->shouldReceive('getCloudinary')->andReturn($cloudinary);
        $this->cloudinary_wrapper->shouldReceive('getUploader')->andReturn($uploader);
        $this->cloudinary_wrapper->makePartial();

        $this->provider->shouldReceive('getCloudinaryWrapper')->andReturn($this->cloudinary_wrapper);
        $this->provider->makePartial();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    /**
     * @covers \PZL\SiteImage\Host\CloudinaryImageHost
     */
    public function testAllAssetsResponse()
    {
        // TODO perhaps a trait for mocking a Cloudinary upload response.
        $data         = [
            'resources' => [
                [
                    'public_id' => $this->faker->uuid,
                    'width'     => $this->faker->numberBetween(10, 800),
                    'height'    => $this->faker->numberBetween(10, 800)
                ]
            ]
        ];
        $api_response = Mockery::mock(ApiResponse::class);
        $api_response->shouldReceive('getArrayCopy')->andReturn($data);
        $this->api->shouldReceive('assets')->andReturn($api_response);

        $response = $this->provider->allAssets();

        assertCount(1, $response);
        self::assertContainsOnlyInstancesOf(SiteImageUploadResponse::class, $response);

    }
}
