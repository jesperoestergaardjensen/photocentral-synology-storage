<?php

namespace PhotoCentralSynologyStorageClient;

use PhotoCentralStorage\Model\ImageDimensions;
use PhotoCentralStorage\Photo;
use PhotoCentralStorage\PhotoCentralStorage;
use PhotoCentralStorage\PhotoCollection;

class SynologyStorage implements PhotoCentralStorage
{
    private ?string $photo_storage_host_address;
    private string $server_base_path;
    private string $client_photo_cache_path;

    public function __construct(
        string $photo_storage_host_address = null,
        string $server_base_path = '/photocentral-storage/public',
        string $client_photo_cache_path = '/photos/cache/synology/'
    ) {
        $this->photo_storage_host_address = $photo_storage_host_address;
        $this->server_base_path = $server_base_path;
        $this->client_photo_cache_path = $client_photo_cache_path;
    }

    public function searchPhotos(string $search_string, ?array $photo_collection_id_list, int $limit = 10): array
    {
        $url = $this->getBaseUrl() . '/Search.php';
        $post_parameters = [
            'search_string'            => $search_string,
            'limit'                    => $limit,
            'photo_collection_id_list' => $photo_collection_id_list,
        ];

        $photo_list_array = $this->doPostRequestWithJsonResponse($url, $post_parameters);
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

        $photo_list_array = $this->doPostRequestWithJsonResponse($url, $post_parameters);
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

        $photo_array = $this->doPostRequestWithJsonResponse($url, $post_parameters);

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

        $photo_collection_list_array = $this->doPostRequestWithJsonResponse($url, $post_parameters);
        $photo_collection_list = [];

        foreach ($photo_collection_list_array as $photo_collection_array) {
            $photo_collection_list[] = PhotoCollection::fromArray($photo_collection_array);
        }

        return $photo_collection_list;
    }

    public function getPhotoPath(
        string $photo_uuid,
        string $photo_collection_id,
        ImageDimensions $image_dimensions
    ): string {
        $url = $this->getBaseUrl() . '/GetPhotoPath.php';

        $post_parameters = [
            'photo_uuid'          => $photo_uuid,
            'photo_collection_id' => $photo_collection_id,
            'image_dimensions'    => $image_dimensions->toArray(),
        ];

        return $this->photo_storage_host_address . $this->doPostRequestWithJsonResponse($url, $post_parameters);
    }

    private function doPostRequestWithJsonResponse(string $url, array $post_parameters, $debug = false)
    {
        // Set the POST data
        $post_options = $this->getPOSTOptions($post_parameters);

        // Create the POST context
        $context = stream_context_create($post_options);

        $json = file_get_contents($url, false, $context);

        if ($debug === true) {
            ini_set('xdebug.var_display_max_depth', 10);
            ini_set('xdebug.var_display_max_children', 256);
            ini_set('xdebug.var_display_max_data', 1024);
            var_dump($json); die();
        } else {
            return json_decode($json, true);
        }
    }

    private function getPOSTOptions(array $post_data): array
    {
        $post_data = http_build_query($post_data);

        // Set the POST options
        return [
            'http' =>
                [
                    'method'  => 'POST',
                    'header'  => "Content-type: application/x-www-form-urlencoded\r\n"
                        . "Content-Length: " . strlen($post_data) . "\r\n",
                    'content' => $post_data,
                ],
        ];
    }

    private function getBaseUrl(): string
    {
        return $this->photo_storage_host_address . $this->server_base_path;
    }

    public function setPhotoCachePath(string $photo_cache_path): void
    {
        $this->client_photo_cache_path = $photo_cache_path;
    }

    public function getPhotoCachePath(): string
    {
        return $this->client_photo_cache_path;
    }
}
