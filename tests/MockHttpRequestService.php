<?php

namespace PhotoCentralSynologyStorageClient\Tests;

use PhotoCentralSynologyStorageClient\HttpRequestService;

class MockHttpRequestService extends HttpRequestService
{
    public string $url;
    public array $post_parameters;
    public string $json_reponse = '';

    public function doPostRequestWithJsonResponse(string $url, array $post_parameters, $debug = false)
    {
        $this->url = $url;
        $this->post_parameters = $post_parameters;

        return $this->json_reponse;
    }
}