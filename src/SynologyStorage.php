<?php

namespace PhotoCentralSynologyStorageClient;

use PhotoCentralStorage\Model\ImageDimensions;
use PhotoCentralStorage\Photo;
use PhotoCentralStorage\PhotoCentralStorage;

class SynologyStorage implements PhotoCentralStorage
{
    private ?string $photo_storage_host_address;
    private string $base_path;

    public function __construct(
        string $photo_storage_host_address = null,
        string $base_path = '/photocentral-storage/public'
    ) {
        $this->photo_storage_host_address = $photo_storage_host_address;
        $this->base_path = $base_path;
    }

    public function searchPhotos(string $search_string, ?array $photo_collection_id_list, int $limit = 10): array
    {
        $url = $this->getBaseUrl() . '/Search.php';
        $post_parameters = [
            'search_string' => $search_string,
            'limit'         => $limit,
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
    ): array
    {
        return [];
    }

    public function getPhoto(string $photo_uuid, string $photo_collection_id): Photo
    {
        return new Photo('abc', '1', 100, 200, 0, time(), time(), null, null, null, null);
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
        return [];
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
            'image_dimensions'    => $image_dimensions,
        ];

        return $this->doPostRequestWithJsonResponse($url, $post_parameters);
    }

    private function doPostRequestWithJsonResponse(string $url, array $post_parameters)
    {
        // Set the POST data
        $post_options = $this->getPOSTOptions($post_parameters);

        // Create the POST context
        $context = stream_context_create($post_options);

        $json = file_get_contents($url, false, $context);

        return json_decode($json, true);
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
        return $this->photo_storage_host_address . $this->base_path;
    }
}
