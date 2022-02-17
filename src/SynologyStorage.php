<?php

namespace PhotoCentralSynologyStorageClient;

use PhotoCentralStorage\Exception\PhotoCentralStorageException;
use PhotoCentralStorage\Model\ImageDimensions;
use PhotoCentralStorage\Model\PhotoQuantity\PhotoQuantityDay;
use PhotoCentralStorage\Model\PhotoQuantity\PhotoQuantityMonth;
use PhotoCentralStorage\Model\PhotoQuantity\PhotoQuantityYear;
use PhotoCentralStorage\Photo;
use PhotoCentralStorage\PhotoCentralStorage;
use PhotoCentralStorage\PhotoCollection;
use function PHPUnit\Framework\isNull;

class SynologyStorage implements PhotoCentralStorage
{
    private ?string $synology_nas_host_address;
    private string $synology_base_path;
    private ?string $client_photo_cache_path;
    private HttpRequestService $http_request_service;

    public function __construct(
        string $synology_nas_host_address = null,
        string $synology_nas_base_path = '/photocentral-storage/public',
        ?string $client_photo_cache_path = '/photos/cache/synology/',
        ?HttpRequestService $http_request_service = null
    ) {
        $this->synology_nas_host_address = $synology_nas_host_address;
        $this->synology_base_path = $synology_nas_base_path;
        $this->client_photo_cache_path = $client_photo_cache_path;
        $this->http_request_service = isNull($http_request_service) ? $http_request_service : new HttpRequestService();
    }

    public function searchPhotos(string $search_string, ?array $photo_collection_id_list, int $limit = 10): array
    {
        $url = $this->getBaseUrl() . '/Search.php';
        $post_parameters = [
            'search_string'            => $search_string,
            'limit'                    => $limit,
            'photo_collection_id_list' => $photo_collection_id_list,
        ];

        $photo_list_array = json_decode($this->http_request_service->doPostRequestWithJsonResponse($url, $post_parameters), true);
        $photo_list = [];
        foreach ($photo_list_array as $photo_array) {
            $photo_list[] = Photo::fromArray($photo_array);
        }

        return $photo_list;
    }

    public function listPhotos(
        array $photo_filters = null,
        array $photo_sorting_parameters = null,
        int $limit = 5
    ): array {
        $url = $this->getBaseUrl() . '/ListPhotos.php';

        $photo_filters_array = null;
        if (is_array($photo_filters)) {
            foreach ($photo_filters as $photo_filter) {
                $photo_filters_array[get_class($photo_filter)] = $photo_filter->toArray();
            }
        }

        $photo_sorting_parameters_array = null;
        if (is_array($photo_sorting_parameters)) {
            foreach ($photo_sorting_parameters as $photo_sorting_parameter) {
                $photo_sorting_parameters_array[get_class($photo_sorting_parameter)] = $photo_sorting_parameter->toArray();
            }
        }

        $post_parameters = [
            'photo_filters'            => $photo_filters_array,
            'photo_sorting_parameters' => $photo_sorting_parameters_array,
            'limit'                    => $limit,
        ];

        $photo_list_array = json_decode($this->http_request_service->doPostRequestWithJsonResponse($url, $post_parameters), true);
        $photo_list = [];
        foreach ($photo_list_array as $photo_array) {
            $photo_list[] = Photo::fromArray($photo_array);
        }

        return $photo_list;
    }

    public function getPhoto(string $photo_uuid, string $photo_collection_id): Photo
    {
        $url = $this->getBaseUrl() . '/GetPhoto.php';
        $post_parameters = [
            'photo_uuid'          => $photo_uuid,
            'photo_collection_id' => $photo_collection_id,
        ];

        $photo_array = json_decode($this->http_request_service->doPostRequestWithJsonResponse($url, $post_parameters), true);

        return Photo::fromArray($photo_array);
    }

    public function softDeletePhoto(string $photo_uuid): bool
    {
        return true;
    }

    public function undoSoftDeletePhoto(string $photo_uuid): bool
    {
        return true;
    }

    public function listPhotoCollections(int $limit): array
    {
        $url = $this->getBaseUrl() . '/ListPhotoCollections.php';
        $post_parameters = [
            'limit' => $limit,
        ];

        $photo_collection_list_array = json_decode($this->http_request_service->doPostRequestWithJsonResponse($url, $post_parameters), true);
        $photo_collection_list = [];

        foreach ($photo_collection_list_array as $photo_collection_array) {
            $photo_collection_list[] = PhotoCollection::fromArray($photo_collection_array);
        }

        return $photo_collection_list;
    }

    public function getPathOrUrlToPhoto(
        string $photo_uuid,
        ImageDimensions $image_dimensions,
        ?string $photo_collection_id
    ): string {
        return "{$this->getBaseUrl()}/DisplayPhoto.php?photo_uuid={$photo_uuid}&image_dimensions_id={$image_dimensions->getId()}";
    }

    private function getBaseUrl(): string
    {
        return $this->synology_nas_host_address . $this->synology_base_path;
    }

    public function setPhotoCache(?string $photo_cache_path): void
    {
        $this->client_photo_cache_path = $photo_cache_path;
    }

    public function getPhotoCache(): ?string
    {
        return $this->client_photo_cache_path;
    }

    /**
     * Will return a path to the photo in the following format:
     *
     * /client_photo_cache_path/image_dimension_id/photo_uuid.jpg
     * e.g.
     * /home/var/wwww/photo-project/public/photos/thumb/935697d459fb3b54f7754a2a369e7e0e.jpg
     *
     * @throws PhotoCentralStorageException
     */
    public function getPathOrUrlToCachedPhoto(
        string $photo_uuid,
        ImageDimensions $image_dimensions,
        string $photo_collection_id
    ): string
    {
        if ($this->client_photo_cache_path === null) {
            throw new PhotoCentralStorageException('No cache pach set');
        } else {
            return $this->client_photo_cache_path . $photo_collection_id . DIRECTORY_SEPARATOR . $image_dimensions->getId() . DIRECTORY_SEPARATOR . $photo_uuid . ".jpg"; // TODO : Could this be handled better?
        }
    }

    public function listPhotoQuantityByYear(?array $photo_collection_id_list): array
    {
        $url = $this->getBaseUrl() . '/ListPhotoQuantityByYear.php';
        $post_parameters = [
            'photo_collection_id_list' => $photo_collection_id_list,
        ];

        $photo_quantity_year_list_array = json_decode($this->http_request_service->doPostRequestWithJsonResponse($url, $post_parameters), true);
        $photo_quantity_year_list = [];
        foreach ($photo_quantity_year_list_array as $photo_quantity_year_array) {
            $photo_quantity_year_list[] = PhotoQuantityYear::fromArray($photo_quantity_year_array);
        }

        return $photo_quantity_year_list;
    }

    public function listPhotoQuantityByMonth(int $year, ?array $photo_collection_id_list): array
    {
        $url = $this->getBaseUrl() . '/ListPhotoQuantityByMonth.php';
        $post_parameters = [
            'year' => $year,
            'photo_collection_id_list' => $photo_collection_id_list,
        ];

        $photo_quantity_month_list_array = json_decode($this->http_request_service->doPostRequestWithJsonResponse($url, $post_parameters), true);
        $photo_quantity_month_list = [];
        foreach ($photo_quantity_month_list_array as $photo_quantity_month_array) {
            $photo_quantity_month_list[] = PhotoQuantityMonth::fromArray($photo_quantity_month_array);
        }

        return $photo_quantity_month_list;
    }

    public function listPhotoQuantityByDay(int $month, int $year, ?array $photo_collection_id_list): array
    {
        $url = $this->getBaseUrl() . '/ListPhotoQuantityByDay.php';
        $post_parameters = [
            'year' => $year,
            'month' => $month,
            'photo_collection_id_list' => $photo_collection_id_list,
        ];

        $photo_quantity_day_list_array = json_decode($this->http_request_service->doPostRequestWithJsonResponse($url, $post_parameters), true);
        $photo_quantity_day_list = [];
        foreach ($photo_quantity_day_list_array as $photo_quantity_day_array) {
            $photo_quantity_day_list[] = PhotoQuantityDay::fromArray($photo_quantity_day_array);
        }

        return $photo_quantity_day_list;
    }
}
