<?php
// This is and endpoint and returns HTML data

// Load secrets
$SECRETS = parse_ini_file(__DIR__ . '/../../php_secrets.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..

// Imports
require_once('./../php/endpoint_helpers.php');
require_once('./../php/libs/blurhash.php');
require_once('./../php/unsplash_api.php');
require_once('./../php/translate.php');
require_once('./../php/lang_placeholders.php');
require_once('./../php/components.php');

// Setup enviroment
setupHeadersHTML();

// Instantiate translator
$translator = new GTranslate($SECRETS['GOOGLE_API_KEY'], isset($_POST['toggleLanguage']) ? 'sv' : 'en');

// Make unsplash instance
$unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY']);

// Load url parameters
$params = getUrlParameters();

// Get the contextual search query and page number
$queryStr = (isset($params["queryStr"]) && is_string($params["queryStr"])) ? rawurldecode($params["queryStr"]) : "";
$orderBy = (isset($params["orderBy"]) && $params["orderBy"] === "relevant") ? "relevant" : "latest";
$autoFetchDetails = (isset($params["autoFetchDetails"]) && ($params["autoFetchDetails"] === "true" || $params["autoFetchDetails"] === true))  ? true : false;
$filterNonGeo = (isset($params["filterNonGeo"]) && ($params["filterNonGeo"] === "true" || $params["filterNonGeo"] === true))  ? true : false;
$translateNonLatin = (isset($params["translateNonLatin"]) && ($params["translateNonLatin"] === "true" || $params["translateNonLatin"] === true))  ? true : false;
$pageNr = isset($params['pageNr']) && is_numeric($params['pageNr']) ? (int)$params['pageNr'] : 1;

$unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY'], $autoFetchDetails);
$images = $unsplash->SearchPhotos($queryStr, 10, $pageNr, $filterNonGeo, $orderBy);
//MARK: Wrap in div for "page"?
echoSearchResultGrid($images, $pageNr, $autoFetchDetails, $translateNonLatin, $translator, isset($_REQUEST["embedGMaps"]));
?>