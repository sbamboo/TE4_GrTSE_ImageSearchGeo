<?php
// This file contians a class to wrap the Unsplash API
class UnsplashAPI {
    private $access_key;
    private $api_url = 'https://api.unsplash.com/';

    public function __construct($access_key) {
        $this->access_key = $access_key;
    }
}

?>