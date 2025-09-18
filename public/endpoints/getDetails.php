<?php
// This is an endpoint and returns HTML data

// Load secrets
$SECRETS = parse_ini_file(__DIR__ . '/../../php_secrets.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..

// Imports
require_once('./../php/endpoint_helpers.php');
require_once('./../php/libs/blurhash.php');
require_once('./../php/caching.php');
require_once('./../php/unsplash_api.php');
require_once('./../php/translate.php');
require_once('./../php/lang_placeholders.php');
require_once('./../php/components.php');

// Setup enviroment
setupHeadersHTML();

// Instantiate Caches
//$mysqli = new mysqli($SECRETS["SQL_URL"], $SECRETS["SQL_USERNAME"], $SECRETS["SQL_PASSWORD"], $SECRETS["SQL_DATABASE"]);
//$imgDetailsCache = new ImgDetailsCacheSQL($mysqli);
$imgDetailsCache = new ImgDetailsCache();

// Instantiate translator
$translator = new GTranslate($SECRETS['GOOGLE_API_KEY'], isset($_POST['toggleLanguage']) ? 'sv' : 'en');

// Make unsplash instance
$unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY'], false, $SECRETS['GOOGLE_API_KEY'], $imgDetailsCache);


// Load url parameters
$params = getUrlParameters();

// Get details for photo and respond
if (!isset($params['id']) || !is_string($params['id'])) {
    respondBadRequest("Missing or invalid 'id' parameter");
}

// Check for the filterNonGeo parameter
$filterNonGeo = isset($params['filterNonGeo']) ? true : false;
$translateNonLatin = isset($params['translateNonLatin']) ? true : false;

// Perform check
try {
    $photoDetails = new UnsplashApiImage($unsplash, $unsplash->GetReducedPhotoDetails($params['id']));

    // If filtering
    if ($filterNonGeo && !$photoDetails->HasGeoData()) {
        respondOK(localize("%location.no-data%"));
    } else {
        // Respond with HTML
        $geoNames = $photoDetails->GetGeoNames();
        $coords = $photoDetails->GetCoordinates();
        $identifiers = $photoDetails->GetIdentifiers();
        $tags = $photoDetails->GetTags();
        echoLocationData(true, $geoNames, $coords, $identifiers, $translateNonLatin, $translator, ["gmaps" => $GMapsLink], $tags);
    }
} catch (Throwable $e) {
    respondError($e);
}

?>