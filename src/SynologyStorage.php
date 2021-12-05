<?php

namespace PhotoCentralSynologyStorageClient;

use PhotoCentralStorage\Model\ImageDimensions;
use PhotoCentralStorage\Model\PhotoSorting\PhotoSorting;
use PhotoCentralStorage\Photo;
use PhotoCentralStorage\PhotoCentralStorage;

class SynologyStorage implements PhotoCentralStorage
{

    public function searchPhotos(string $search_string): array
    {
        return [];
    }

    public function listPhotos(array $photo_filters = null, PhotoSorting $photo_sorting = null, int $limit = 5): array
    {
        return [];
    }

    public function getPhoto(string $photo_uuid): Photo
    {
        return new Photo('abc', '1', 100, 200, 0, time(), time(), null, null, null);
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

    public function getPhotoPath(string $photo_uuid, ImageDimensions $image_dimensions): string
    {
        return '';
    }
}
