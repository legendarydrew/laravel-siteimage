<?php

namespace PZL\SiteImage\Tests\CloudinaryImageHost;


use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\ApiResponse;
use Exception;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Depends;
use PZL\SiteImage\CloudinaryWrapper;
use PZL\SiteImage\Host\CloudinaryImageHost;
use PZL\SiteImage\Tests\TestCase;

#[CoversClass(CloudinaryImageHost::class)]
class BuildTransformationsTest extends TestCase
{

    private array $transformations;

    protected function setUp(): void
    {
        parent::setUp();

        $this->api      = Mockery::mock(AdminApi::class);
        $this->wrapper  = Mockery::mock(CloudinaryWrapper::class);
        $this->provider = Mockery::mock(CloudinaryImageHost::class)->makePartial();

        $this->wrapper->shouldReceive('getApi')->andReturn($this->api);
        $this->provider->shouldReceive('getCloudinaryWrapper')->andReturn($this->wrapper);

        $this->placeholder_url = $this->faker->picsumUrl();
        $this->transformations = $this->provider->getTransformations();
    }

    public function testTransformationsArePresent()
    {
        self::assertNotEmpty($this->transformations);
    }

    /**
     * @throws Exception
     */
    #[Depends('testTransformationsArePresent')]
    public function testUpdatesTransformations()
    {
        $this->api->shouldReceive('transformations')->andReturn(new ApiResponse([
            'transformations' => [],
        ], []));
        $this->api->shouldReceive('updateTransformation')
                  ->times(count($this->transformations))
                  ->andReturn(new ApiResponse(['message' => 'created'], []));

        $this->provider->buildTransformations();
    }

    /**
     * @throws Exception
     */
    #[Depends('testTransformationsArePresent')]
    public function testCreatesTransformations()
    {
        $this->api->shouldReceive('transformations')->andReturn(new ApiResponse([
            'transformations' => [],
        ], []));
        $this->api->shouldReceive('updateTransformation')
                  ->andThrow(new Exception('Create instead.'));
        $this->api->shouldReceive('createTransformation')
                  ->times(count($this->transformations))
                  ->andReturn(new ApiResponse(['message' => 'created'], []));

        $this->provider->buildTransformations();
    }

    /**
     * @throws Exception
     */
    #[Depends('testTransformationsArePresent')]
    public function testDeletesExtraTransformations()
    {
        $this->api->shouldReceive('transformations')->andReturn(new ApiResponse([
            'transformations' => [
                ['name' => 'delete_me'],
            ],
        ], []));
        $this->api->shouldReceive('updateTransformation')
                  ->times(count($this->transformations))
                  ->andReturn(new ApiResponse(['message' => 'created'], []));

        $this->api->shouldReceive('deleteTransformation')
                  ->with($this->equalTo('delete_me'))
                  ->once()
                  ->andReturn(new ApiResponse(['message' => 'deleted'], []));

        $this->provider->buildTransformations();
    }
}
