<?php

namespace PZL\SiteImage\Tests;

use Cloudinary\Api\Admin\AdminApi;
use Cloudinary\Api\ApiResponse;
use Cloudinary\Api\Exception\ApiError;
use Cloudinary\Api\Upload\UploadApi;
use Cloudinary\Asset\Media;
use Cloudinary\Cloudinary;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use PZL\SiteImage\CloudinaryWrapper;
use PZL\SiteImage\SiteImageUploadResponse;

/**
 * CloudinaryWrapperTest
 * inherited from https://github.com/jrm2k6/cloudder
 * and before that https://github.com/teepluss/laravel4-cloudinary
 *
 * @package PZL\SiteImage\Tests
 */
#[CoversClass(CloudinaryWrapper::class)]
class CloudinaryWrapperTest extends TestCase
{
    private AdminApi|Mockery\Mock $api;

    private UploadApi|Mockery\Mock $uploader;

    private CloudinaryWrapper $cloudinary_wrapper;

    private Mockery\MockInterface $media;

    public function setUp(): void
    {
        parent::setUp();

        $this->api      = Mockery::mock(AdminApi::class);
        $cloudinary     = Mockery::mock(Cloudinary::class);
        $this->uploader = Mockery::mock(UploadApi::class);
        $this->media    = Mockery::mock('overload:' . Media::class);

        $this->cloudinary_wrapper = Mockery::mock(CloudinaryWrapper::class);
        $this->cloudinary_wrapper->shouldReceive('getApi')->andReturn($this->api);
        $this->cloudinary_wrapper->shouldReceive('getCloudinary')->andReturn($cloudinary);
        $this->cloudinary_wrapper->shouldReceive('getUploader')->andReturn($this->uploader);
        $this->cloudinary_wrapper->makePartial();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    public function test_it_should_set_uploaded_result_when_uploading_picture()
    {
        // given
        $filename         = 'filename';
        $defaults_options = [
            'public_id' => null,
            'tags'      => []
        ];
        $expected_result  = ['public_id' => '123456789'];

        $this->cloudinary_wrapper->shouldReceive('getResult')->andReturn(new ApiResponse($expected_result, []));

        $this->uploader->shouldReceive('upload')
                       ->once()
                       ->with($filename, $defaults_options)
                       ->andReturn(new ApiResponse($expected_result, []));

        // when
        $this->cloudinary_wrapper->upload($filename);

        // then
        $result = $this->cloudinary_wrapper->getResult()->getArrayCopy();
        self::assertEquals($expected_result['public_id'], $result['public_id']);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_set_uploaded_result_when_uploading_picture_unsigned()
    {
        // given
        $filename         = 'filename';
        $defaults_options = [
            'public_id' => null,
            'tags'      => []
        ];

        $upload_preset = 'preset';

        $expected_result = ['public_id' => '123456789'];

        $this->cloudinary_wrapper->shouldReceive('getResult')->andReturn(new ApiResponse($expected_result, []));

        $this->uploader->shouldReceive('unsignedUpload')
                       ->once()
                       ->with($filename, $upload_preset, $defaults_options)
                       ->andReturn(new ApiResponse($expected_result, []));

        // when
        $this->cloudinary_wrapper->unsignedUpload($filename, null, $upload_preset);

        // then
        /**
         * @var SiteImageUploadResponse $result
         */
        $result = $this->cloudinary_wrapper->getResult()->getArrayCopy();
        self::assertEquals($expected_result['public_id'], $result['public_id']);
    }

    public function test_it_should_set_uploaded_result_when_uploading_private_picture()
    {
        // given
        $filename         = 'filename';
        $defaults_options = [
            'public_id' => null,
            'tags'      => [],
            'type'      => 'private'
        ];

        $expected_result = ['public_id' => '123456789'];

        $this->cloudinary_wrapper->shouldReceive('getResult')->andReturn(new ApiResponse($expected_result, []));

        $this->uploader->shouldReceive('upload')
                       ->once()
                       ->with($filename, $defaults_options)
                       ->andReturn(new ApiResponse($expected_result, []));

        // when
        $this->cloudinary_wrapper->upload($filename, null, ['type' => 'private']);

        // then
        $result = $this->cloudinary_wrapper->getResult()->getArrayCopy();
        self::assertEquals($expected_result['public_id'], $result['public_id']);
    }

    public function test_it_should_returns_image_url_when_calling_show()
    {
        // given
        $filename = 'filename';

        // when
        $this->media->shouldReceive('fromParams')->once()->andReturn('wha');

        $this->cloudinary_wrapper->show($filename);
    }

    public function test_it_should_returns_https_image_url_when_calling_secure_show()
    {
        // given
        $filename = 'filename';
        $this->media->shouldReceive('fromParams')
                    ->once()
                    ->with($filename, ['secure' => true])
                    ->andReturn('woah');

        // when
        $this->cloudinary_wrapper->secureShow($filename);
    }

    public function test_it_should_returns_image_url_when_calling_show_private_url()
    {
        // given
        $filename = 'filename';
        $this->uploader->shouldReceive('privateDownloadUrl')->once()->with($filename, 'png', []);

        // when
        $this->cloudinary_wrapper->showPrivateUrl($filename, 'png');
    }

    public function test_it_should_returns_image_url_when_calling_private_download_url()
    {
        // given
        $filename = 'filename';
        $this->uploader->shouldReceive('privateDownloadUrl')->once()->with($filename, 'png', []);

        // when
        $this->cloudinary_wrapper->privateDownloadUrl($filename, 'png');
    }

    public function test_it_should_call_api_rename_when_calling_rename()
    {
        // given
        $from = 'from';
        $to   = 'to';

        $this->uploader->shouldReceive('rename')->with($from, $to, [])->once()->andReturn([]);

        // when
        $this->cloudinary_wrapper->rename($from, $to);
    }

    public function test_it_should_call_api_destroy_when_calling_destroy_image()
    {
        // given
        $pid = 'pid';
        $this->uploader->shouldReceive('destroy')->with($pid, [])->once()->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->destroyImage($pid);
    }

    public function test_it_should_call_api_destroy_when_calling_destroy()
    {
        // given
        $pid = 'pid';
        $this->uploader->shouldReceive('destroy')->with($pid, [])->once()->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->destroy($pid);
    }

    public function test_verify_delete_alias_returns_boolean()
    {
        // given
        $pid = 'pid';
        $this->uploader->shouldReceive('destroy')->with($pid, [])->once()->andReturn(new ApiResponse(['result' => 'ok'], []));

        // when
        $deleted = $this->cloudinary_wrapper->delete($pid);
        self::assertTrue($deleted);
    }

    public function test_it_should_call_api_add_tag_when_calling_add_tag()
    {
        $pids = ['pid1', 'pid2'];
        $tag  = 'tag';

        $this->uploader->shouldReceive('addTag')->once()->with($tag, $pids, [])->andReturn(new ApiResponse([], []));

        $this->cloudinary_wrapper->addTag($tag, $pids);
    }

    public function test_it_should_call_api_remove_tag_when_calling_add_tag()
    {
        $pids = ['pid1', 'pid2'];
        $tag  = 'tag';

        $this->uploader->shouldReceive('removeTag')->once()->with($tag, $pids, [])->andReturn(new ApiResponse([], []));

        $this->cloudinary_wrapper->removeTag($tag, $pids);
    }

    public function test_it_should_call_api_rename_tag_when_calling_add_tag()
    {
        $pids = ['pid1', 'pid2'];
        $tag  = 'tag';

        $this->uploader->shouldReceive('replaceTag')->once()->with($tag, $pids, [])->andReturn(new ApiResponse([], []));

        $this->cloudinary_wrapper->replaceTag($tag, $pids);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_call_api_delete_resources_when_calling_destroy_images()
    {
        $pids = ['pid1', 'pid2'];
        $this->api->shouldReceive('deleteAssets')->once()->with($pids, [])->andReturn(new ApiResponse([], []));

        $this->cloudinary_wrapper->destroyImages($pids);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_call_api_delete_resources_when_calling_delete_resources()
    {
        $pids = ['pid1', 'pid2'];
        $this->api->shouldReceive('deleteAssets')->once()->with($pids, [])->andReturn(new ApiResponse([], []));

        $this->cloudinary_wrapper->deleteAssets($pids);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_call_api_delete_resources_by_prefix_when_calling_delete_resources_by_prefix()
    {
        $prefix = 'prefix';
        $this->api->shouldReceive('deleteAssetsByPrefix')->once()->with($prefix, [])->andReturn(new ApiResponse([], []));

        $this->cloudinary_wrapper->deleteAssetsByPrefix($prefix);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_call_api_delete_all_resources_when_calling_delete_all_resources()
    {
        $this->api->shouldReceive('deleteAllAssets')->once()->with([])->andReturn(new ApiResponse([], []));

        $this->cloudinary_wrapper->deleteAllAssets();
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_call_api_delete_resources_by_tag_when_calling_delete_resources_by_tag()
    {
        $tag = 'tag1';
        $this->api->shouldReceive('deleteAssetsByTag')->once()->with($tag, [])->andReturn(new ApiResponse([], []));

        $this->cloudinary_wrapper->deleteAssetsByTag($tag);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_call_api_delete_derived_resources_when_calling_delete_derived_resources()
    {
        $pids = ['pid1', 'pid2'];
        $this->api->shouldReceive('deleteDerivedAssets')->once()->with($pids)->andReturn(new ApiResponse([], []));

        $this->cloudinary_wrapper->deleteDerivedAssets($pids);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_set_uploaded_result_when_uploading_video()
    {
        // given
        $filename         = 'filename';
        $defaults_options = [
            'public_id'     => null,
            'tags'          => [],
            'resource_type' => 'video'
        ];

        $expected_result = ['public_id' => '123456789'];

        $this->cloudinary_wrapper->shouldReceive('getResult')->andReturn(new ApiResponse($expected_result, []));

        $this->uploader->shouldReceive('upload')->once()->with($filename, $defaults_options)->andReturn(new ApiResponse($expected_result, []));

        // when
        $this->cloudinary_wrapper->uploadVideo($filename);

        // then
        /**
         * @var SiteImageUploadResponse $result
         */
        $result = $this->cloudinary_wrapper->getResult()->getArrayCopy();
        self::assertEquals($expected_result['public_id'], $result['public_id']);
    }

    public function test_it_should_call_api_create_archive_when_generating_archive()
    {
        // given
        $this->uploader->shouldReceive('createArchive')->once()->with(
            ['tag' => 'kitten', 'mode' => 'create', 'target_public_id' => null]
        )->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->createArchive(['tag' => 'kitten']);
    }

    public function test_it_should_call_api_create_archive_with_correct_archive_name()
    {
        // given
        $this->uploader->shouldReceive('createArchive')->once()->with(
            ['tag' => 'kitten', 'mode' => 'create', 'target_public_id' => 'kitten_archive']
        )->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->createArchive(['tag' => 'kitten'], 'kitten_archive');
    }

    public function test_it_should_call_api_download_archive_url_when_generating_archive()
    {
        // given
        $this->uploader->shouldReceive('downloadArchiveUrl')->once()->with(
            ['tag' => 'kitten', 'target_public_id' => null]
        )->andReturn('');

        // when
        $this->cloudinary_wrapper->downloadArchiveUrl(['tag' => 'kitten']);
    }

    public function test_it_should_call_api_download_archive_url_with_correct_archive_name()
    {
        // given
        $this->uploader->shouldReceive('downloadArchiveUrl')->once()->with(
            ['tag' => 'kitten', 'target_public_id' => 'kitten_archive']
        )->andReturn('');

        // when
        $this->cloudinary_wrapper->downloadArchiveUrl(['tag' => 'kitten'], 'kitten_archive');
    }

    public function test_it_should_show_response_when_calling_resources()
    {
        // given
        $this->api->shouldReceive('assets')->once()->with([])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->assets();
    }

    public function test_it_should_show_response_when_calling_resources_by_ids()
    {
        $pids = ['pid1', 'pid2'];

        $options = ['test', 'test1'];

        // given
        $this->api->shouldReceive('assetsByIds')->once()->with($pids, $options)->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->assetsByIds($pids, $options);
    }

    public function test_it_should_show_response_when_calling_resources_by_tag()
    {
        $tag = 'tag';

        // given
        $this->api->shouldReceive('assetsByTag')->once()->with($tag, [])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->assetsByTag($tag);
    }

    public function test_it_should_show_response_when_calling_resources_by_moderation()
    {
        $kind   = 'manual';
        $status = 'pending';

        // given
        $this->api->shouldReceive('assetsByModeration')->once()->with($kind, $status, [])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->assetsByModeration($kind, $status);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_show_list_when_calling_tags()
    {
        // given
        $this->api->shouldReceive('tags')->once()->with([])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->tags();
    }

    public function test_it_should_show_response_when_calling_resource()
    {
        $pid = 'pid';

        // given
        $this->api->shouldReceive('asset')->once()->with($pid, [])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->asset($pid);
    }

    public function test_it_should_update_a_resource_when_calling_update()
    {
        $pid     = 'pid';
        $options = ['tags' => 'tag1'];

        // given
        $this->api->shouldReceive('update')->once()->with($pid, $options)->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->update($pid, $options);
    }

    public function test_it_should_show_transformations_list_when_calling_transformations()
    {
        // given
        $this->api->shouldReceive('transformations')->once()->with([])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->transformations();
    }

    public function test_it_should_show_one_transformation_when_calling_transformation()
    {
        $transformation = "c_fill,h_100,w_150";

        // given
        $this->api->shouldReceive('transformation')->once()->with($transformation, [])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->transformation($transformation);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_delete_a_transformation_when_calling_delete_transformation()
    {
        $transformation = "c_fill,h_100,w_150";

        // given
        $this->api->shouldReceive('deleteTransformation')->once()->with($transformation, [])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->deleteTransformation($transformation);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_update_a_transformation_when_calling_update_transformation()
    {
        $transformation = "c_fill,h_100,w_150";
        $updates        = ["allowed_for_strict" => 1];

        // given
        $this->api->shouldReceive('updateTransformation')->once()->with($transformation, $updates)->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->updateTransformation($transformation, $updates);
    }

    public function test_it_should_create_a_transformation_when_calling_create_transformation()
    {
        $name       = "name";
        $definition = "c_fill,h_100,w_150";

        // given
        $this->api->shouldReceive('createTransformation')->once()->with($name, $definition)->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->createTransformation($name, $definition);
    }

    public function test_it_should_restore_resources_when_calling_restore()
    {
        $pids = ['pid1', 'pid2'];

        // given
        $this->api->shouldReceive('restore')->once()->with($pids, [])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->restore($pids);
    }

    public function test_it_should_show_upload_mappings_list_when_calling_upload_mappings()
    {
        // given
        $this->api->shouldReceive('uploadMappings')->once()->with([])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->uploadMappings();
    }

    public function test_it_should_upload_mapping_when_calling_upload_mapping()
    {
        $pid = 'pid1';

        // given
        $this->api->shouldReceive('uploadMapping')->once()->with($pid)->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->uploadMapping($pid);
    }

    public function test_it_should_create_upload_mapping_when_calling_create_upload_mapping()
    {
        $pid = 'pid1';

        // given
        $this->api->shouldReceive('createUploadMapping')->once()->with($pid, [])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->createUploadMapping($pid);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_delete_upload_mapping_when_calling_delete_upload_mapping()
    {
        $pid = 'pid1';

        // given
        $this->api->shouldReceive('deleteUploadMapping')->once()->with($pid)->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->deleteUploadMapping($pid);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_update_upload_mapping_when_calling_update_upload_mapping()
    {
        $pid = 'pid1';

        // given
        $this->api->shouldReceive('updateUploadMapping')->once()->with($pid, [])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->updateUploadMapping($pid);
    }

    public function test_it_should_show_upload_presets_list_when_calling_upload_presets()
    {
        // given
        $this->api->shouldReceive('uploadPresets')->once()->with([])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->uploadPresets();
    }


    public function test_it_should_upload_preset_when_calling_upload_preset()
    {
        $pid = 'pid1';

        // given
        $this->api->shouldReceive('uploadPreset')->once()->with($pid, [])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->uploadPreset($pid);
    }

    public function test_it_should_create_upload_preset_when_calling_create_upload_preset()
    {
        $pid = 'pid1';

        // given
        $this->api->shouldReceive('createUploadPreset')->once()->with(['name' => $pid])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->createUploadPreset($pid);
    }

    public function test_it_should_delete_upload_preset_when_calling_delete_upload_preset()
    {
        $pid = 'pid1';

        // given
        $this->api->shouldReceive('deleteUploadPreset')->once()->with($pid)->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->deleteUploadPreset($pid);
    }

    /**
     * @throws ApiError
     */
    public function test_it_should_update_upload_preset_when_calling_update_upload_preset()
    {
        $pid = 'pid1';

        // given
        $this->api->shouldReceive('updateUploadPreset')->once()->with($pid, [])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->updateUploadPreset($pid);
    }

    public function test_it_should_show_root_folders_list_when_calling_root_folders()
    {
        // given
        $this->api->shouldReceive('rootFolders')->once()->with([])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->rootFolders();
    }

    public function test_it_should_subfolders_when_calling_subfolders()
    {
        $pid = 'pid1';

        // given
        $this->api->shouldReceive('subfolders')->once()->with($pid, [])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->subfolders($pid);
    }

    public function test_it_should_show_usage_list_when_calling_usage()
    {
        // given
        $this->api->shouldReceive('usage')->once()->with([])->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->usage();
    }

    public function test_it_should_show_ping_list_when_calling_ping()
    {
        // given
        $this->api->shouldReceive('ping')->once()->andReturn(new ApiResponse([], []));

        // when
        $this->cloudinary_wrapper->ping();
    }
}
