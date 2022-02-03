<?php

namespace PZL\SiteImage\Tests\CloudinaryImageHost;


use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Asset\Media;
use Cloudinary\Cloudinary;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use PZL\SiteImage\CloudinaryWrapper;
use PZL\SiteImage\Host\CloudinaryImageHost;
use PZL\SiteImage\SiteImageUploadResponse;
use PZL\SiteImage\Tests\TestCase;

class UploadTest extends TestCase
{
    use WithFaker;

    /**
     * @var Mockery\MockInterface
     */
    private $media;

    public function setUp(): void
    {
        parent::setUp();

        $this->provider = Mockery::mock(CloudinaryImageHost::class);
        $api            = Mockery::mock(AdminApi::class);
        $cloudinary     = Mockery::mock(Cloudinary::class);
        $uploader = Mockery::mock(UploadApi::class);
        $this->cloudinary_wrapper = Mockery::mock(CloudinaryWrapper::class);
        $this->media = Mockery::mock('overload:' . Media::class);

        $this->cloudinary_wrapper->shouldReceive('getApi')->andReturn($api);
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

    public function testUploadResponse()
    {
        // TODO perhaps a trait for mocking a Cloudinary upload response.
        $data = [
            'public_id' => $this->faker->uuid,
            'width' => $this->faker->numberBetween(10, 800),
            'height' => $this->faker->numberBetween(10, 800)
        ];
        $api_response = Mockery::mock(ApiResponse::class);
        $api_response->shouldReceive('getArrayCopy')->andReturn($data);
        $this->cloudinary_wrapper->shouldReceive('getResult')->andReturn($api_response);
        $this->cloudinary_wrapper->shouldReceive('upload')->andReturnSelf();

        $response = $this->provider->upload($this->faker->image, 'test');

        self::assertInstanceOf(SiteImageUploadResponse::class, $response);
        self::assertEquals($data['public_id'], $response->public_id);
        self::assertEquals($data['width'], $response->width);
        self::assertEquals($data['height'], $response->height);

    }
}
