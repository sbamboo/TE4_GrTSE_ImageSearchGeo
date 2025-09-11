<?php
// This is and endpoint and returns HTML data

// Load secrets
$SECRETS = parse_ini_file(__DIR__ . '/../../php_secrets.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..

// Imports
require_once('./../php/endpoint_helpers.php');
require_once('./../php/libs/blurhash.php');
require_once('./../php/unsplash_api.php');
require_once('./../php/components.php');

// Setup enviroment
setupHeadersHTML();

// Make unsplash instance
$unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY']);

// Load url parameters
$params = getUrlParameters();

// Get the contextual search query and page number
$queryStr = rawurldecode($params['queryStr']) ?? '';
$orderBy = $params['orderBy'] ?? 'relevant'; // "relevant" or "latest"
$autoFetchDetails = $params['autoFetchDetails'] === 'true' ? true : false;
$pageNr = isset($params['pageNr']) && is_numeric($params['pageNr']) ? (int)$params['pageNr'] : 1;

$unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY'], $autoFetchDetails);
$images = $unsplash->SearchPhotos($queryStr, 10, $pageNr);
echo '<div class="php-endpoint-response">';
foreach ($images as $image) {
    echoImageHTML($image);
}
echo '</div>';
?>