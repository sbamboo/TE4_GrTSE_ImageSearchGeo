<?php
// This file contians a class to wrap the Unsplash API
class UnsplashAPI {
    private $access_key;
    private $api_url = 'https://api.unsplash.com/';

    public function __construct($access_key) {
        $this->access_key = $access_key;
    }

    // lower level make GET request function without curl extension
    function make_get_request($endpoint, $params = []) {
        $url = $this->api_url . $endpoint . '?' . http_build_query(array_merge($params, ['client_id' => $this->access_key]));
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => "Accept: application/json\r\n"
            ]
        ];
        $context = stream_context_create($options);
        $response = file_get_contents($url, false, $context);
        if ($response === FALSE) {
            throw new Exception("Error making GET request to Unsplash API");
        }
        return json_decode($response, true);
    }
}

/*
// Example getting random photo
$unsplash = new UnsplashAPI($SECRETS['unsplash_access_key']);

try {
    $random_photo = $unsplash->make_get_request('photos/random');
    print_r($random_photo);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
*/
?>