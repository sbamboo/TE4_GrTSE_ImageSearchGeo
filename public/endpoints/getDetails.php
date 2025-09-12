<?php
// This is and endpoint and returns JSON data

// Load secrets
$SECRETS = parse_ini_file(__DIR__ . '/../../php_secrets.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..

// Imports
require_once('./../php/endpoint_helpers.php');
require_once('./../php/libs/blurhash.php');
require_once('./../php/unsplash_api.php');

// Setup enviroment
setupHeadersJSON();

// Make unsplash instance
$unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY']);

// Load url parameters
$params = getUrlParameters();

// Get details for photo and respond
if (!isset($params['id']) || !is_string($params['id'])) {
    respondBadRequest("Missing or invalid 'id' parameter");
}

// Check for the filterNonGeo parameter
$filterNonGeo = isset($params['filterNonGeo']) ? true : false;

// Perform check
try {
    $photoDetails = $unsplash->GetPhotoDetails($params['id']);

    // If filtering
    if ($filterNonGeo && !$photoDetails->HasGeoData()) {
        respondOK("Photo has no geo data, filtered out");
    } else {
        respondOKContent($photoDetails->ToArray());
    }
} catch (Throwable $e) {
    respondError($e);
}

?>