<?php

namespace PhotoCentralSynologyStorageClient\Tests;

use PhotoCentralStorage\Exception\PhotoCentralStorageException;
use PhotoCentralStorage\Model\ImageDimensions;
use PhotoCentralStorage\Model\PhotoFilter\PhotoCollectionIdFilter;
use PhotoCentralStorage\Model\PhotoQuantity\PhotoQuantityDay;
use PhotoCentralStorage\Model\PhotoQuantity\PhotoQuantityMonth;
use PhotoCentralStorage\Model\PhotoQuantity\PhotoQuantityYear;
use PhotoCentralStorage\Photo;
use PhotoCentralStorage\PhotoCentralStorage;
use PhotoCentralStorage\PhotoCollection;
use PhotoCentralStorage\Tests\Unit\PhotoCentralStorageTestBase;
use PhotoCentralSynologyStorageClient\SynologyStorage;

class SynologyStorageClientTest extends PhotoCentralStorageTestBase
{
    private MockHttpRequestService $mock_http_request_service;
    private string $host = '100.0.0.1';
    private string $synology_nas_base_path = '/photocentral-storage/public';
    private array $expected_photo_colletion_id_list;


    public function initializePhotoCentralStorage(): PhotoCentralStorage
    {
        $this->mock_http_request_service = new MockHttpRequestService();

        return new SynologyStorage(
            $this->host,
            $this->synology_nas_base_path,
            null,
            $this->mock_http_request_service
        );
    }

    public function setupExpectedProperties()
    {
        $expected_photo_collection_list_a = new PhotoCollection(UUIDService::create(), 'List A', true, 'test list a', null);
        $expected_photo_collection_list_b = new PhotoCollection(UUIDService::create(), 'List B', true, 'test list b', null);

        $this->expected_photo_colletion_list = [$expected_photo_collection_list_a, $expected_photo_collection_list_b];
        $this->expected_photo_colletion_id_list = [$expected_photo_collection_list_a->getId(), $expected_photo_collection_list_b->getId()];

        $this->expected_list_photos_photo_uuid_list = [
            'fd03f50cb54942882bcdcc4e6b5fffe5',
            'c9d9287f153e87b4f83cdea7f32db649',
        ];

        $this->expected_search_string = 'bike';
        $this->expected_photo_uuid_for_get = '6d1858ef4ee6897f18fc0d0381d92c7d';
        $this->expected_photo_uuid_list_for_search = [
            'c9d9287f153e87b4f83cdea7f32db649',
            'c9d9287f153e87b4f83cdea7f32db649',
        ];
        $this->expected_photo_uuid_for_soft_delete = 'fd03f50cb54942882bcdcc4e6b5fffe5';
        $this->expected_photo_quantity_by_year_list = [
            new PhotoQuantityYear('2022', 2022, 2),
            new PhotoQuantityYear('2019', 2019, 2),
            new PhotoQuantityYear('2017', 2017, 2),
        ];

        $this->expected_photo_quantity_by_month_list = [
            new PhotoQuantityMonth('02', 2, 2),
        ];
        $this->expected_photo_quantity_by_day_list = [
            new PhotoQuantityDay('11', 11, 2),
        ];

        $this->expected_non_existing_photo_uuid = UUIDService::create();
    }

    public function setUp(): void
    {
        $this->photo_central_storage = $this->initializePhotoCentralStorage();
        $this->setupExpectedProperties();
    }
/*
    public static function setUpBeforeClass(): void
    {
    }
*/
    public function testSearchPhotos()
    {
        $expected_photo = $this->createDummyPhoto();

        $expected_url_called = $this->host . $this->synology_nas_base_path . '/Search.php'; // TODO : improve
        $expected_post_parameters = [
            'search_string'            => 'basketball',
            'limit'                    => 20,
            'photo_collection_id_list' => null,
        ];

        $this->mock_http_request_service->json_reponse = json_encode([$expected_photo->toArray()]);
        $search_result_list = $this->photo_central_storage->searchPhotos('basketball', null, 20);

        $this->assertEquals($expected_url_called, $this->mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, $this->mock_http_request_service->post_parameters);
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

        $expected_url_called = $this->host . $this->synology_nas_base_path . '/ListPhotoCollections.php'; // TODO : improve
        $expected_post_parameters = [
            'limit'                    => 100,
        ];

        $this->mock_http_request_service->json_reponse = json_encode($expected_photo_collection_list_array);
        $photo_collection_list = $this->photo_central_storage->listPhotoCollections(100);

        $this->assertEquals($expected_url_called, $this->mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, $this->mock_http_request_service->post_parameters);
        $this->assertEquals([$expected_photo_collection_list_a, $expected_photo_collection_list_b], $photo_collection_list);
    }

    public function testGetPhoto()
    {
        $expected_photo_collection_id = UUIDService::create();
        $expected_photo = $this->createDummyPhoto();

        $expected_url_called = $this->host . $this->synology_nas_base_path . '/GetPhoto.php'; // TODO : improve
        $expected_post_parameters = [
            'photo_uuid' => $expected_photo->getPhotoUuid(),
            'photo_collection_id' => $expected_photo_collection_id
        ];

        $this->mock_http_request_service->json_reponse = json_encode($expected_photo->toArray());
        $actual_photo = $this->photo_central_storage->getPhoto($expected_photo->getPhotoUuid(), $expected_photo_collection_id);

        $this->assertEquals($expected_url_called, $this->mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, $this->mock_http_request_service->post_parameters);
        $this->assertEquals($expected_photo, $actual_photo);
    }

    /**
     * @depends testNoPhotoCachePathSetException
     */
    public function testGetPathOrUrlToCachedPhoto()
    {
        // Prepare
        $expected_photo_uuid = UUIDService::create();
        $expected_photo_collection_id = UUIDService::create();
        $photo_cache_path = 'test';
        $image_dimensions = ImageDimensions::createFromId(ImageDimensions::SD_ID);
        $expetedPathUrl = $photo_cache_path . $expected_photo_collection_id . '/' . $image_dimensions->getId() . '/' . $expected_photo_uuid . ".jpg";

        // Test method
        $this->photo_central_storage->setPhotoCache($photo_cache_path);
        $actual_photo_cache_path = $this->photo_central_storage->getPhotoCache();
        $this->assertEquals($photo_cache_path, $actual_photo_cache_path);
        $actual = $this->photo_central_storage->getPathOrUrlToCachedPhoto($expected_photo_uuid, $image_dimensions, $expected_photo_collection_id);
        $this->assertEquals($expetedPathUrl, $actual);
    }

    public function testNoPhotoCachePathSetException()
    {
        $expected_photo_uuid = UUIDService::create();
        $expected_photo_collection_id = UUIDService::create();
        $image_dimensions = ImageDimensions::createFromId(ImageDimensions::SD_ID);

        // Test exception
        $this->expectException(PhotoCentralStorageException::class);
        $this->photo_central_storage->getPathOrUrlToCachedPhoto($expected_photo_uuid, $image_dimensions, $expected_photo_collection_id);
    }

    public function testGetPathOrUrlToPhoto()
    {
        $expected_photo_uuid = UUIDService::create();
        $expected_photo_collection_id = UUIDService::create();
        $image_dimensions = ImageDimensions::createFromId(ImageDimensions::SD_ID);
        $actual = $this->photo_central_storage->getPathOrUrlToPhoto($expected_photo_uuid, $image_dimensions, $expected_photo_collection_id);

        // TODO : Improve
        $expected = '100.0.0.1/photocentral-storage/public/DisplayPhoto.php?photo_uuid='.$expected_photo_uuid.'&image_dimensions_id=sd';
        $this->assertEquals($expected, $actual);
    }

    public function testlistPhotoQuantityByYear()
    {
        $expected_url_called = $this->host . $this->synology_nas_base_path . '/ListPhotoQuantityByYear.php'; // TODO : improve
        $expected_post_parameters = [
            'photo_collection_id_list' => $this->expected_photo_colletion_id_list,
        ];

        $expected_photo_quantity_by_year_list_array = [];

        foreach ($this->expected_photo_quantity_by_year_list as $expected_photo_quantity_by_year) {
            /** @var PhotoQuantityYear $expected_photo_quantity_by_year */
            $expected_photo_quantity_by_year_list_array[] = $expected_photo_quantity_by_year->toArray();
        }

        $this->mock_http_request_service->json_reponse = json_encode($expected_photo_quantity_by_year_list_array);

        parent::testlistPhotoQuantityByYear();

        $this->assertEquals($expected_url_called, $this->mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, $this->mock_http_request_service->post_parameters);
    }

    public function testlistPhotoQuantityByMonth()
    {
        $expected_url_called = $this->host . $this->synology_nas_base_path . '/ListPhotoQuantityByMonth.php'; // TODO : improve
        $expected_post_parameters = [
            'photo_collection_id_list' => $this->expected_photo_colletion_id_list,
            'year' => '2022'
        ];

        $expected_photo_quantity_by_month_list_array = [];

        foreach ($this->expected_photo_quantity_by_month_list as $expected_photo_quantity_by_month) {
            /** @var PhotoQuantityMonth $expected_photo_quantity_by_month */
            $expected_photo_quantity_by_month_list_array[] = $expected_photo_quantity_by_month->toArray();
        }

        $this->mock_http_request_service->json_reponse = json_encode($expected_photo_quantity_by_month_list_array);

        parent::testlistPhotoQuantityByMonth();

        $this->assertEquals($expected_url_called, $this->mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, $this->mock_http_request_service->post_parameters);
    }

    public function testlistPhotoQuantityByDay()
    {
        $expected_url_called = $this->host . $this->synology_nas_base_path . '/ListPhotoQuantityByDay.php'; // TODO : improve
        $expected_post_parameters = [
            'photo_collection_id_list' => $this->expected_photo_colletion_id_list,
            'year' => '2022',
            'month' => '2'
        ];

        $expected_photo_quantity_by_day_list_array = [];

        foreach ($this->expected_photo_quantity_by_day_list as $expected_photo_quantity_by_day) {
            /** @var PhotoQuantityDay $expected_photo_quantity_by_day */
            $expected_photo_quantity_by_day_list_array[] = $expected_photo_quantity_by_day->toArray();
        }

        $this->mock_http_request_service->json_reponse = json_encode($expected_photo_quantity_by_day_list_array);

        parent::testlistPhotoQuantityByDay();

        $this->assertEquals($expected_url_called, $this->mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, $this->mock_http_request_service->post_parameters);

    }

    public function testSoftDeleteExistingPhoto()
    {
        $expected_url_called = $this->host . $this->synology_nas_base_path . '/SoftDeletePhoto.php'; // TODO : improve
        $expected_post_parameters = [
            'photo_uuid' => $this->expected_photo_uuid_for_soft_delete
        ];

        $this->mock_http_request_service->json_reponse = json_encode(['success' => true]);
        parent::testSoftDeleteExistingPhoto();

        $this->assertEquals($expected_url_called, $this->mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, $this->mock_http_request_service->post_parameters);
    }

    public function testSoftDeleteNonExistingPhoto()
    {
        $expected_url_called = $this->host . $this->synology_nas_base_path . '/SoftDeletePhoto.php'; // TODO : improve
        $expected_post_parameters = [
            'photo_uuid' => $this->expected_non_existing_photo_uuid
        ];

        $this->mock_http_request_service->json_reponse = json_encode(['success' => false]);
        parent::testSoftDeleteNonExistingPhoto();

        $this->assertEquals($expected_url_called, $this->mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, $this->mock_http_request_service->post_parameters);
    }

    /**
     * @depends testSoftDeleteExistingPhoto
     */
    public function testUndoSoftDeleteExistingPhoto()
    {
        $expected_url_called = $this->host . $this->synology_nas_base_path . '/UndoSoftDeletePhoto.php'; // TODO : improve
        $expected_post_parameters = [
            'photo_uuid' => $this->expected_photo_uuid_for_soft_delete
        ];

        $this->mock_http_request_service->json_reponse = json_encode(['success' => true]);
        parent::testUndoSoftDeleteExistingPhoto();

        $this->assertEquals($expected_url_called, $this->mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, $this->mock_http_request_service->post_parameters);

    }

    public function testUndoSoftDeleteNonExistingPhoto()
    {
        $expected_url_called = $this->host . $this->synology_nas_base_path . '/UndoSoftDeletePhoto.php'; // TODO : improve
        $expected_post_parameters = [
            'photo_uuid' => $this->expected_non_existing_photo_uuid
        ];

        $this->mock_http_request_service->json_reponse = json_encode(['success' => false]);
        parent::testUndoSoftDeleteNonExistingPhoto();

        $this->assertEquals($expected_url_called, $this->mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, $this->mock_http_request_service->post_parameters);
    }

    public function testListPhotos()
    {
        $photo_colleciton_id_list = [];
        foreach ($this->expected_photo_colletion_list as $expected_photo_collection) {
            $photo_colleciton_id_list[] = $expected_photo_collection->getId();
        }

        $expected_url_called = $this->host . $this->synology_nas_base_path . '/ListPhotos.php'; // TODO : improve
        $expected_post_parameters = [
            'photo_filters'            => [PhotoCollectionIdFilter::class => (new PhotoCollectionIdFilter($photo_colleciton_id_list))->toArray()],
            'photo_sorting_parameters' => null,
            'limit'                    => 2,
        ];

        $expected_json_response = [];
        foreach ($this->expected_list_photos_photo_uuid_list as $photo_uuid) {
            $expected_json_response[] = ($this->createDummyPhoto($photo_uuid))->toArray();
        }

        $this->mock_http_request_service->json_reponse = json_encode($expected_json_response);
        parent::testListPhotos();

        $this->assertEquals($expected_url_called, $this->mock_http_request_service->url);
        $this->assertEquals($expected_post_parameters, $this->mock_http_request_service->post_parameters);
    }

    private function createDummyPhoto(string $photo_uuid = null): Photo
    {
        $photo_uuid = $photo_uuid ?? UUIDService::create();

        return new Photo(
            $photo_uuid,
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
