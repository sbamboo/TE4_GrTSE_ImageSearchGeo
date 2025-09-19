<?php
// Setup enviroment
//ini_set('log_errors', 1);
//ini_set('error_log', './../php-errors.log');
//ini_set('display_errors', 0); // prevent sending to stderr

// Get all the secrets from php.ini file
$SECRETS = parse_ini_file(__DIR__ . '/../php_secrets.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..
$CONFIG = parse_ini_file(__DIR__ . '/../config.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..

// Imports
require_once('./php/caching.php');
require_once('./php/libs/blurhash.php');
require_once('./php/unsplash_api.php');
require_once('./php/translate.php');
require_once('./php/lang_placeholders.php');
require_once('./php/components.php');


// Instantiate translator
$translator = new GTranslate($SECRETS['GOOGLE_API_KEY'], isset($_REQUEST['toggleLanguage']) ? 'sv' : 'en');

// Instantiate Caches
$useFileCache = true;
if (isset($CONFIG["USE_SQL_CACHE"]) && ($CONFIG["USE_SQL_CACHE"] === true || $CONFIG["USE_SQL_CACHE"] === "true" || $CONFIG["USE_SQL_CACHE"] === "True")) {
    $useFileCache = false;

    $mysqli = new mysqli($CONFIG["MYSQL_ADDRESS"], $CONFIG["MYSQL_USERNAME"], $CONFIG["MYSQL_PASSWORD"], $CONFIG["MYSQL_DATABASE"]);
    
    if (!$mysqli || $mysqli->connect_errno) {
        error_log("Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error);
        $useFileCache = true;
    }

    if ($useFileCache === false) {
        if (isset($CONFIG["SQL_CACHE_TABLE"]) && isset($CONFIG["SQL_CACHE_TTL"]) && is_int($CONFIG["SQL_CACHE_TTL"])) {
            $imgDetailsCache = new ImgDetailsCacheSQL($mysqli, $CONFIG["SQL_CACHE_TABLE"], $CONFIG["SQL_CACHE_TTL"]);
        } else if (isset($CONFIG["SQL_CACHE_TABLE"])) {
            $imgDetailsCache = new ImgDetailsCacheSQL($mysqli, $CONFIG["SQL_CACHE_TABLE"]);
        } else if (isset($CONFIG["SQL_CACHE_TTL"]) && is_int($CONFIG["SQL_CACHE_TTL"])) {
            $imgDetailsCache = new ImgDetailsCacheSQL($mysqli, null, $CONFIG["SQL_CACHE_TTL"]);
        } else {
            $imgDetailsCache = new ImgDetailsCacheSQL($mysqli);
        }
    }
}

if ($useFileCache === true) {
    if (isset($CONFIG["JSON_CACHE_FILE"]) && (isset($CONFIG["JSON_CACHE_TTL"]) && is_int($CONFIG["JSON_CACHE_TTL"]))) {
        $imgDetailsCache = new ImgDetailsCache($CONFIG["JSON_CACHE_FILE"], $CONFIG["JSON_CACHE_TTL"]);
    } else if (isset($CONFIG["JSON_CACHE_FILE"])) {
        $imgDetailsCache = new ImgDetailsCache($CONFIG["JSON_CACHE_FILE"]);
    } else if (isset($CONFIG["JSON_CACHE_TTL"]) && is_int($CONFIG["JSON_CACHE_TTL"])) {
        $imgDetailsCache = new ImgDetailsCache(null, $CONFIG["JSON_CACHE_TTL"]);
    } else {
        $imgDetailsCache = new ImgDetailsCache();
    }
}

// Handle incomming search form POST, parsing out "queryStr" (string), "orderBy" (string:enum), "autoFetchDetails" (bool)
$queryStr = $_REQUEST['queryStr'] ?? '';
$orderBy = $_REQUEST['orderBy'] ?? 'relevant'; // "relevant" or "latest"
$autoFetchDetails = isset($_REQUEST['autoFetchDetails']);
$filterNonGeo = isset($_REQUEST['filterNonGeo']);
$translateNonLatin = isset($_REQUEST['translateNonLatin']);
$toggleLayout = isset($_REQUEST['toggleLayout']);
$toggleLanguage = isset($_REQUEST['toggleLanguage']) ? true : false;
$embedGMaps = isset($_REQUEST['embedGMaps']) ? true : false;
$highlightTags = isset($_REQUEST['highlightTags']) ? true : false;
$toggleMapMode = isset($_REQUEST['toggleMapMode']) ? true : false;

$hasSearched = !empty($queryStr);
$imagesPerPage = isset($_REQUEST['imagesPerPage']) ? intval($_REQUEST['imagesPerPage']) : intval($CONFIG['IMAGES_PER_PAGE']);
$pageNr = isset($_REQUEST['pageNr']) ? intval($_REQUEST['pageNr']) : 1;
$searchInfo = null;

// Perform search
if(!empty($queryStr)){
    $unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY'], $autoFetchDetails, $SECRETS['GOOGLE_API_KEY'], $imgDetailsCache);
    $images = $unsplash->SearchPhotos($queryStr, $imagesPerPage, $pageNr, $filterNonGeo, $orderBy);

    // If length is 0 
    if (count($images) === 0) {
        //MARK: Should probably search again atleast once but for now show info text
        $searchInfo = "No results in first page, check next page or try changing filter.";

        // Search again for page +1 also
        //$pageNr = 2;
        //$images = $unsplash->SearchPhotos($queryStr, 10, $pageNr, $filterNonGeo, $orderBy);
    }
}

// Count is based on HasGeo if FilterNonGeo is enabled
if(!empty($queryStr)) {
    $imagesCount = count($images);
} else {
    $imagesCount = 0;
}
?>

<!DOCTYPE html>
<html lang="sv" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/helpers.css">
    <link rel="stylesheet" href="./css/lowwidth.css">
    <link rel="stylesheet" href="./css/ui.css">
    <link rel="stylesheet" href="./css/main.css">

    <script>
        function initMap() {
            window.map = new google.maps.Map(document.getElementById("map"), {
                center: { lat: 0, lng: 0 },
                zoom: 2,
                mapId: "<?=$SECRETS['GOOGLE_MAP_ID']?>",
                tilt: 0,
                heading: 0,
            });

            if (window._pendingMarkers) {
                window._pendingMarkers.forEach(args => addImageMarker(...args));
                window._pendingMarkers = [];
            }
        }
    </script>
    
    <script src="./js/localstorage.js"></script>
    <script src="./js/theme.js"></script>
    <script src="./js/popups.js"></script>
    <script src="./js/main.js"></script>

    <!-- Context Meta (Use already validated values) -->
    <meta name="queryStr" content="<?php echo htmlspecialchars($queryStr, ENT_QUOTES); ?>">
    <meta name="orderBy" content="<?php echo htmlspecialchars($orderBy, ENT_QUOTES); ?>">
    <meta name="autoFetchDetails" content="<?php echo $autoFetchDetails ? 'true' : 'false'; ?>">
    <meta name="filterNonGeo" content="<?php echo $filterNonGeo ? 'true' : 'false'; ?>">
    <meta name="translateNonLatin" content="<?php echo $translateNonLatin ? 'true' : 'false'; ?>">
    <meta name="toggleLayout" content="<?php echo $toggleLayout ? 'true' : 'false'; ?>">
    <meta name="toggleLanguage" content="<?php echo $toggleLanguage ? 'true' : 'false'; ?>">
    <meta name="embedGMaps" content="<?php echo $embedGMaps ? 'true' : 'false'; ?>">
    <meta name="highlightTags" content="<?php echo $highlightTags ? 'true' : 'false'; ?>">
    <meta name="toggleMapMode" content="<?php echo $toggleMapMode ? 'true' : 'false'; ?>">
    <meta name="imagesPerPage" content="<?php echo $imagesPerPage ?>">
    <meta name="imagesCount" content="<?php echo $imagesCount ?>">
    <meta name="pageNr" content="<?php echo $pageNr ?>">
    <?php
    // If we have a cache initialized call GetAllKnownTags() and then output as meta comma joined
    if ($imgDetailsCache) {
        $allKnownTags = $imgDetailsCache->GetAllKnownTags();
        if ($allKnownTags && count($allKnownTags) > 0) {
            $tagsStr = implode(", ", $allKnownTags);
            echo '<meta name="cachedTags" content="' . htmlspecialchars($tagsStr, ENT_QUOTES) . '">';
        }
    }
    ?>

    <title>Image Search</title>
</head>
<body>
    <!-- Wrapper for overlays -->
    <div id="overlay-container">
        <div id="popup-container">

            <div id="localstorage-prompt" style="display:none;">
                <div id="localstorage-prompt-box">
                    <p><?php echo localize("%localstorage.prompt%") ?></p>
                    <div class="hflex-center">
                        <button id="localstorage-decline" class="button"><?php echo localize("%localstorage.decline%") ?></button>
                        <button id="localstorage-accept" class="button"><?php echo localize("%localstorage.accept%") ?></button>
                    </div>
                </div>
            </div>

            <div id="settings" style="display:none;">
                <div id="settings-container-box">
                    <div id="settings-top-box">
                        <div><!--Empty div ;) --></div>
                        <a id="settings-head-line"><?php echo localize("%settings.button%") ?></a>
                        <button id="settings-closer" class="popup-closer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M18 6l-12 12" /><path d="M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <label class="fake-checkbox" for="auto-fetch-details"><span><?php echo localize("%settings.autofetch%") ?></span><span class="checkmark"></span></label>
                    <p class="text-info-smaller"><span><?php echo localize("%settings.autofetch.desc%") ?>.</span><span class="checkmark"></span></p>

                    <label class="fake-checkbox" for="filter-non-geo"><span><?php echo localize("%settings.filter-non-geo%") ?></span><span class="checkmark"></span></label>
                    <p class="text-info-smaller"><span><?php echo localize("%settings.filter-non-geo.desc%") ?>.</span><span class="checkmark"></span></p>

                    <label class="fake-checkbox" for="translate-non-latin"><span><?php echo localize("%settings.translate-non-latin%") ?></span><span class="checkmark"></span></label>
                    <p class="text-info-smaller"><span><?php echo localize("%settings.translate-non-latin.desc%") ?>.</span><span class="checkmark"></span></p>

                    <label class="fake-checkbox" for="embed-gmaps"><span><?php echo localize("%settings.embed-gmaps%") ?></span><span class="checkmark"></span></label>
                    <p class="text-info-smaller"><span><?php echo localize("%settings.embed-gmaps.desc%") ?>.</span><span class="checkmark"></span></p>

                    <label class="fake-checkbox" for="highlight-tags"><span><?php echo localize("%settings.highlight-tags%") ?></span><span class="checkmark"></span></label>
                    <p class="text-info-smaller"><span><?php echo localize("%settings.highlight-tags.desc%") ?>.</span><span class="checkmark"></span></p>

                
                    <label for="theme">
                        <span><?php echo localize("%settings.theme%") ?></span>
                        <select id="theme" name="theme">
                            <option value="light" <?php if(isset($_POST['theme']) && $_POST['theme'] === 'light') echo 'selected'; ?>><?php echo localize("%settings.theme.light%") ?></option>
                            <option value="dark" <?php if(isset($_POST['theme']) && $_POST['theme'] === 'dark') echo 'selected'; ?>><?php echo localize("%settings.theme.dark%") ?></option>
                            <option value="system" <?php if(!isset($_POST['theme']) || (isset($_POST['theme']) && $_POST['theme'] === 'system')) echo 'selected'; ?>><?php echo localize("%settings.theme.system%") ?></option>
                        </select>
                    </label>
                    <p class="text-info-smaller"><span><?php echo localize("%settings.theme.desc%") ?>.</span></p>

                    <div id="accept-localstorage-consent-container">  
                        <span><?php echo localize("%settings.accept-localstorage%") ?></span>
                        <button id="accept-localstorage-consent" class="button"><?php echo localize("%settings.accept-localstorage-btn%") ?></button>
                    </div>
                    <p id="accept-localstorage-consent-info" class="text-info-smaller"><span><?php echo localize("%settings.accept-localstorage.desc%") ?>.</span></p>

                    <div id="revoke-localstorage-consent-container">
                        <span><?php echo localize("%settings.revoke-localstorage%") ?></span>
                        <button id="revoke-localstorage-consent" class="button"><?php echo localize("%settings.revoke-localstorage-btn%") ?></button>
                    </div>
                    <p id="revoke-localstorage-consent-info" class="text-info-smaller"><span><?php echo localize("%settings.revoke-localstorage.desc%") ?>.</span></p>
                </div>
            </div>

            <div id="gmaps-popup" style="display:none;">
                <iframe 
                    id="iframe-interactive-map"
                    width="600"
                    height="450"
                    style="border:0"
                    loading="lazy"
                    allowfullscreen
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps/embed/v1/place?key=<?= $SECRETS['GOOGLE_API_KEY'] ?>&q=35.6617773,139.7040506">
                </iframe>
                <button id="map-closer" class="popup-closer">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M18 6l-12 12" /><path d="M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <div id="img-metadata-popup" style="display:none;">
                <div id="img-metadata-container-box">
                    <div id="img-metadata-top-box">
                        <div><!--Empty div ;) --></div>
                        <a id="img-metadata-head-line">Image Metadata</a>
                        <button id="img-metadata-closer" class="popup-closer">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M18 6l-12 12" /><path d="M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div id="img-metadata-content">
                        <p id="img-metadata-tags">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M7.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" />
                                <path d="M3 6v5.172a2 2 0 0 0 .586 1.414l7.71 7.71a2.41 2.41 0 0 0 3.408 0l5.592 -5.592a2.41 2.41 0 0 0 0 -3.408l-7.71 -7.71a2 2 0 0 0 -1.414 -.586h-5.172a3 3 0 0 0 -3 3z" />
                            </svg>
                            <strong>Tags:</strong> <span></span>
                        </p>
                        <p id="img-metadata-camera-name">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1a2 2 0 0 0 2 2h1a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2" />
                                <path d="M9 13a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                            </svg>
                            <strong>Camera Name:</strong> <span></span>
                        </p>
                        <p id="img-metadata-camera-make">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1a2 2 0 0 0 2 2h1a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2" />
                                <path d="M9 13a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                            </svg>
                            <strong>Camera Make:</strong> <span></span>
                        </p>
                        <p id="img-metadata-camera-model">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M5 7h1a2 2 0 0 0 2 -2a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1a2 2 0 0 0 2 2h1a2 2 0 0 1 2 2v9a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2v-9a2 2 0 0 1 2 -2" />
                                <path d="M9 13a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                            </svg>
                            <strong>Camera Model:</strong> <span></span>
                        </p>
                        <p id="img-metadata-exposure-time">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M3.6 20.4l16.8 -16.8" />
                                <path d="M6 8h4m-2 -2v4" />
                                <path d="M14 16h4" />
                                <path d="M3 5a2 2 0 0 1 2 -2h14a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-14a2 2 0 0 1 -2 -2z" />
                            </svg>
                            <strong>Exposure Time:</strong> <span></span>
                        </p>
                        <p id="img-metadata-aperture">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/>
                                <path d="M3.6 15h10.55"/>
                                <path d="M6.551 4.938l3.26 10.034"/>
                                <path d="M17.032 4.636l-8.535 6.201"/>
                                <path d="M20.559 14.51l-8.535 -6.201"/>
                                <path d="M12.257 20.916l3.261 -10.034"/>
                            </svg>
                            <strong>Aperture:</strong> <span></span>
                        </p>
                        <p id="img-metadata-focal-length">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/>
                                <path d="M4 8v-2a2 2 0 0 1 2 -2h2"/>
                                <path d="M4 16v2a2 2 0 0 0 2 2h2"/>
                                <path d="M16 4h2a2 2 0 0 1 2 2v2"/>
                                <path d="M16 20h2a2 2 0 0 0 2 -2v-2"/>
                            </svg>
                            <strong>Focal Length:</strong> <span></span>
                        </p>
                        <p id="img-metadata-iso">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 14 14" fill="currentColor">
                                <path d="m 10.006823,5.68736 c -0.5360739,0 -0.8659762,0.46734 -0.8659762,1.27832 0,0.81098 0.3299023,1.31956 0.8659642,1.31956 0.536061,0 0.865963,-0.50858 0.865963,-1.31956 0,-0.81098 -0.329877,-1.27832 -0.865951,-1.27832 z m 2.742329,-2.26398 -11.4983043,0 C 1.112317,3.42337 1,3.53568 1,3.67422 l 0,6.65156 c 0,0.13855 0.112317,0.25085 0.2508477,0.25085 l 11.4983043,0 C 12.887696,10.57663 13,10.46433 13,10.32578 L 13,3.67422 C 13,3.53567 12.887696,3.42337 12.749152,3.42337 Z m -9.4912848,5.79655 -1.182107,0 0,-4.46726 1.182107,0 0,4.46726 z m 2.4054158,0.0825 c -0.5635669,0 -1.2095874,-0.20618 -1.6906756,-0.63916 L 4.6461209,7.8523 c 0.3161433,0.25427 0.7147653,0.43298 1.044655,0.43298 0.3573827,0 0.5085811,-0.11684 0.5085811,-0.31615 0,-0.21304 -0.2268039,-0.28177 -0.6048062,-0.43297 L 5.0378445,7.30242 C 4.5567689,7.10998 4.1375272,6.7045 4.1375272,6.05846 c 0,-0.76286 0.6872724,-1.38829 1.6631952,-1.38829 0.5085811,0 1.0721355,0.19245 1.4844914,0.59793 L 6.6941665,6.01036 C 6.3917697,5.79732 6.1305871,5.68735 5.8007224,5.68735 c -0.2886629,0 -0.4673542,0.10308 -0.4673542,0.30239 0,0.21303 0.2542842,0.28864 0.6597795,0.44671 L 6.5360823,6.6495 c 0.5567062,0.21992 0.8728495,0.60479 0.8728495,1.22334 2.51e-5,0.75603 -0.632274,1.42954 -1.7456488,1.42954 z m 4.34354,0 c -1.2371053,0 -2.0755511,-0.86595 -2.0755511,-2.33672 0,-1.47076 0.8384709,-2.29549 2.0755511,-2.29549 1.23708,0 2.075539,0.8316 2.075539,2.29549 2.5e-5,1.47077 -0.838446,2.33672 -2.075539,2.33672 z"/>
                            </svg>
                            <strong>ISO:</strong> <span></span>
                        </p>
                    </div>
                </div>
            </div>

        </div>
        <div id="portal-container"></div>
    </div>


    <!-- Main Content, With initial page -->
    <div id="search-container" class="vflex-center">
        <form id="search-form" class="hflex-vcenter" action="" method="post" autocomplete="on">
            <label id="search-label" for="search-bar"><?php echo localize("%search.title%") ?></label>
            <div id="higlight-container">
                <div id="higlight" class="highlight-layer"></div>  
                <input id="search-bar" class="highlight-layer" type="search" 
                    name="queryStr" value="<?php echo htmlspecialchars($queryStr); ?>">  
            </div>

            <input id="auto-fetch-details" class="hidden-checkbox" type="checkbox" name="autoFetchDetails" <?php if (!$hasSearched || $autoFetchDetails) echo 'checked'; ?>>
           
            <input id="filter-non-geo" class="hidden-checkbox" type="checkbox" name="filterNonGeo" <?php if (!$hasSearched || $filterNonGeo) echo 'checked'; ?>>
  
            <input id="translate-non-latin" class="hidden-checkbox" type="checkbox" name="translateNonLatin" <?php if (!$hasSearched || $translateNonLatin) echo 'checked'; ?>>
  
            <input id="embed-gmaps" class="hidden-checkbox" type="checkbox" name="embedGMaps" <?php if (!$hasSearched || $embedGMaps) echo 'checked'; ?>>

            <input id="highlight-tags" class="hidden-checkbox" type="checkbox" name="highlightTags" <?php if (!$hasSearched || $highlightTags) echo 'checked'; ?>>
            
            <input id="toggle-layout" type="checkbox" name="toggleLayout"<?php if(!$hasSearched || $toggleLayout) echo 'checked' ?>>
            
            <input id="toggle-map-mode" type="checkbox" name="toggleMapMode"<?php if($hasSearched && $toggleMapMode) echo 'checked' ?>>

            <div id="input-labels-container" class="hflex-center">
                <input id="search-button" type="submit" value="<?php echo localize("%search.button-text%")?>">

                <label id="toggle-language-label" class="vflex vflex-vcenter" for="toggle-language">
                    <svg id="swedish-flag" xmlns="http://www.w3.org/2000/svg" width="24" height="16" viewBox="0 0 16 10">
                        <rect width="16" height="10" fill="#005cbf"/>
                        <rect x="5" width="2" height="10" fill="#ffc720"/>
                        <rect y="4" width="16" height="2" fill="#ffc720"/>    
                    </svg>
                    <svg id="english-flag" xmlns="http://www.w3.org/2000/svg" width="24" height="12" viewBox="0 0 60 30">
                        <rect width="60" height="30" fill="#012169"/>

                        <polygon points="0,0 6,0 60,24 60,30 54,30 0,6" fill="#fff"/>
                        <polygon points="60,0 60,6 6,30 0,30 0,24 54,0" fill="#fff"/>

                        <polygon points="0,0 3,0 60,27 60,30 57,30 0,3" fill="#C8102E"/>
                        <polygon points="60,0 60,3 3,30 0,30 0,27 57,0" fill="#C8102E"/>

                        <rect x="25" width="10" height="30" fill="#fff"/>
                        <rect y="10" width="60" height="10" fill="#fff"/>

                        <rect x="27" width="6" height="30" fill="#C8102E"/>
                        <rect y="12" width="60" height="6" fill="#C8102E"/>
                    </svg>
                </label>
                
                <input id="toggle-language" type="checkbox" name="toggleLanguage"<?php if($toggleLanguage) {echo 'checked'; }?> onchange="this.form.submit()">
                <?php
                    echoFilter(
                        [
                            "relevant" => localize("%search.sorting.relevance%"),
                            "latest" => localize("%search.sorting.latest%")
                        ],
                        $orderBy
                    );
                ?>

                <div id="settings-button">  
                    <svg  xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" />
                        <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
                    </svg>
                </div>
            </div>
        </form>
        <p class="text-info-smaller"><?php echo localize("%search.desc%") ?></p>
    </div>
    <main class="vflex-center">
        <div id="results-filter-bar" class="hflex">
            <label id="toggle-layout-label" for="toggle-layout">
                <svg id="grid-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M4 4h6v6H4z" />
                    <path d="M14 4h6v6h-6z" />
                    <path d="M4 14h6v6H4z" />
                    <path d="M14 14h6v6h-6z" />
                </svg>
                <svg id="list-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M4 6h16" />
                    <path d="M4 12h16" />
                    <path d="M4 18h16" />
                </svg>
            </label>
            <label id="toggle-map-label" for="toggle-map-mode">
                <svg id="map-icon" xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round">
                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                    <path d="M12 18.5l-3 -1.5l-6 3v-13l6 -3l6 3l6 -3v7.5" />
                    <path d="M9 4v13" />
                    <path d="M15 7v5.5" />
                    <path d="M21.121 20.121a3 3 0 1 0 -4.242 0c.418 .419 1.125 1.045 2.121 1.879c1.051 -.89 1.759 -1.516 2.121 -1.879z" />
                    <path d="M19 18v.01" />
                </svg>
            </label>
        </div>
        <div id="gmaps-result-container">
            <!-- This is the container where the map will load -->
            <div id="map"></div>
        </div>

        <div id="search-result-container" class="reoderable-image-container php-endpoint-response">
            <?php
                if ($searchInfo) {
                    echo "<p id=\"search-info\">$searchInfo</p>";
                }

                if(empty($queryStr)){
                    return;
                }

                echoSearchResultGrid($images, $pageNr, $autoFetchDetails, $translateNonLatin, $translator, isset($_REQUEST["embedGMaps"]));
            ?>
        </div>
        <div class="hflex-center">
            <div class="vflex">
                <button id="get-more-images-button"><?php echo localize("%search.next-page-btn.next%") ?></button>
                <p id="get-more-images-info" style="display:none;"></p>
            </div>
        </div>
    </main>

    <!-- Scripts At End to ensure exec order -->
    <?php if($toggleLanguage){
        ?><script src="https://maps.googleapis.com/maps/api/js?key=<?= $SECRETS['GOOGLE_API_KEY'] ?>&callback=initMap&v=weekly&language=sv&libraries=marker&loading=async" async defer></script><?php
    } 
    else{
        ?><script src="https://maps.googleapis.com/maps/api/js?key=<?= $SECRETS['GOOGLE_API_KEY'] ?>&callback=initMap&v=weekly&language=en&libraries=marker&loading=async" async defer></script><?php
    }
    ?>
</body>
</html>