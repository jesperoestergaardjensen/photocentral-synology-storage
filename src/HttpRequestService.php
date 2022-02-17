<?php

namespace PhotoCentralSynologyStorageClient;

class HttpRequestService
{
    public function doPostRequestWithJsonResponse(string $url, array $post_parameters, $debug = false)
    {
        // Set the POST data
        $post_options = self::getPOSTOptions($post_parameters);

        // Create the POST context
        $context = stream_context_create($post_options);

        $json = file_get_contents($url, false, $context);

        if ($debug === true) {
            ini_set('xdebug.var_display_max_depth', 10);
            ini_set('xdebug.var_display_max_children', 256);
            ini_set('xdebug.var_display_max_data', 1024);
            var_dump($json); die();
        } else {
            return $json;
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
}
