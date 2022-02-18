<?php

namespace PhotoCentralSynologyStorageClient\Tests;

use Cassandra\UuidInterface;
use PhotoCentralStorage\Photo;
use PhotoCentralStorage\PhotoCollection;
use PhotoCentralSynologyStorageClient\SynologyStorage;
use PHPUnit\Framework\TestCase;

class SynologyStorageTest extends TestCase
{
    private static SynologyStorage $synology_storage;
    private static MockHttpRequestService $mock_http_request_service;
    private static string $host = '100.0.0.1';
    private static string $synology_nas_base_path = '/photocentral-storage/public';

    public static function setUpBeforeClass(): void
    {
        self::$mock_http_request_service = new MockHttpRequestService();
        self::$synology_storage = new SynologyStorage(self::$host, self::$synology_nas_base_path, null,
            self::$mock_http_request_service);
    }

    public function testSearch()
    {
        $expected_photo = $this->createDummyPhoto();

        $expected_url_called = self::$host . self::$synology_nas_base_path . '/Search.php'; // TODO : improve
        $expected_post_parameters = [
            'search_string'            => 'basketball',
            'limit'                    => 20,
            'photo_collection_id_list' => null,
        ];

        self::$mock_http_request_service->json_reponse = json_encode([$expected_photo->toArray()]);
        $search_result_list = self::$synology_storage->searchPhotos('basketball', null, 20);

        $this->assertEquals($expected_url_called, self::$mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, self::$mock_http_request_service->post_parameters);
        $this->assertEquals([$expected_photo], $search_result_list);
    }

    public function testListPhotoCollections()
    {
        $expected_photo_collection_list_a = new PhotoCollection(UUIDService::create(), 'List A', true, 'test list a', null);
        $expected_photo_collection_list_b = new PhotoCollection(UUIDService::create(), 'List B', true, 'test list b', null);
        $expected_photo_collection_list_array = [
            $expected_photo_collection_list_a->toArray(),
            $expected_photo_collection_list_b->toArray()
        ];

        $expected_url_called = self::$host . self::$synology_nas_base_path . '/ListPhotoCollections.php'; // TODO : improve
        $expected_post_parameters = [
            'limit'                    => 100,
        ];

        self::$mock_http_request_service->json_reponse = json_encode($expected_photo_collection_list_array);
        $photo_collection_list = self::$synology_storage->listPhotoCollections(100);

        $this->assertEquals($expected_url_called, self::$mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, self::$mock_http_request_service->post_parameters);
        $this->assertEquals([$expected_photo_collection_list_a, $expected_photo_collection_list_b], $photo_collection_list);
    }

    public function testGetPhoto()
    {
        $expected_photo_collection_id = UUIDService::create();
        $expected_photo = $this->createDummyPhoto();

        $expected_url_called = self::$host . self::$synology_nas_base_path . '/GetPhoto.php'; // TODO : improve
        $expected_post_parameters = [
            'photo_uuid' => $expected_photo->getPhotoUuid(),
            'photo_collection_id' => $expected_photo_collection_id
        ];

        self::$mock_http_request_service->json_reponse = json_encode($expected_photo->toArray());
        $actual_photo = self::$synology_storage->getPhoto($expected_photo->getPhotoUuid(), $expected_photo_collection_id);

        $this->assertEquals($expected_url_called, self::$mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, self::$mock_http_request_service->post_parameters);
        $this->assertEquals($expected_photo, $actual_photo);
    }

    private function createDummyPhoto(): Photo
    {
        return new Photo(
            UUIDService::create(),
            1,
            100,
            120,
            1,
            time(),
            time(),
            time(),
            null,
            'Nikon',
            'LX200'
        );
    }
}
