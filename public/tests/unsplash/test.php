<?php
require_once('./../../php/unsplash_api.php');

$SECRETS = parse_ini_file(__DIR__ . '/../../../php_secrets.ini', false, INI_SCANNER_TYPED); // Replace with your Unsplash API Access Key


$unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY']);

try {
    $random_photo = $unsplash->make_get_request('photos/random');
    echo '<pre>';
    print_r($random_photo);
    echo '</pre>';
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>