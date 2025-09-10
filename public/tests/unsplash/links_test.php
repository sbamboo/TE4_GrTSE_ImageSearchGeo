<?php
require_once('./../../php/unsplash_api.php');

$SECRETS = parse_ini_file(__DIR__ . '/../../../php_secrets.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..

$unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY']);

$response = $unsplash->GetRandomImage();

echo '<pre>';   
print_r($response);
echo '</pre>';

?>