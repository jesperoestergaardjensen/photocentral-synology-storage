<?php

namespace PhotoCentralSynologyStorageClient\Tests;

use PhotoCentralStorage\Photo;
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
        $expected_photo = new Photo(
            'fgfdgf',
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
}
