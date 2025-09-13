<?php
require_once('./../../php/unsplash_api.php');

$SECRETS = parse_ini_file(__DIR__ . '/../../../php_secrets.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..

$unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY']);

$images = $unsplash->SearchPhotos("Dog", 10, 1);

foreach ($images as $image) {
    $profile = $image->GetUserInfo();
    echo '<pre>';
    print_r($image);
    //print_r($profile);
    
    echo '</pre>';
}

?>