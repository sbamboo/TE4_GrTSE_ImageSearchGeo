<?php
// Setup enviroment
//ini_set('log_errors', 1);
//ini_set('error_log', './../php-errors.log');
//ini_set('display_errors', 0); // prevent sending to stderr

// Get all the secrets from php.ini file
$SECRETS = parse_ini_file(__DIR__ . '/../php_secrets.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..
session_start();

// Imports
require_once('./php/libs/blurhash.php');
require_once('./php/unsplash_api.php');
require_once('./php/translate.php');
require_once('./php/langplaceholders.php');
require_once('./php/components.php');

// Instantiate translator
$translator = new GTranslate($SECRETS['GTRANSLATE_API_KEY']);

// Handle incomming search form POST, parsing out "queryStr" (string), "orderBy" (string:enum), "autoFetchDetails" (bool)
$queryStr = $_POST['queryStr'] ?? '';
$orderBy = $_POST['orderBy'] ?? 'relevant'; // "relevant" or "latest"
$autoFetchDetails = isset($_POST['autoFetchDetails']);
$filterNonGeo = isset($_POST['filterNonGeo']);
$translateNonLatin = isset($_POST['translateNonLatin']);
$toggleLayout = isset($_POST['toggleLayout']);
$toggleLanguage = isset($_POST['toggleLanguage']);

$hasSearched = !empty($queryStr);
$pageNr = 1;
$searchInfo = null;

// Perform search
if(!empty($queryStr)){
    $unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY'], $autoFetchDetails);
    $images = $unsplash->SearchPhotos($queryStr, 10, $pageNr, $filterNonGeo, $orderBy);

    // If length is 0 
    if (count($images) === 0) {
        //MARK: Should probably search again atleast once but for now show info text
        $searchInfo = "No results in first page, check next page or try changing filter.";

        // Search again for page +1 also
        //$pageNr = 2;
        //$images = $unsplash->SearchPhotos($queryStr, 10, $pageNr, $filterNonGeo, $orderBy);
    }
}
?>

<!DOCTYPE html>
<html lang="sv" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/helpers.css">
    <link rel="stylesheet" href="./css/main.css">
    
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
    <meta name="pageNr" content="<?php echo $pageNr ?>">

    <title>Image Search</title>
</head>
<body>
    <!-- Wrapper for overlays -->
    <div id="overlay-container">
        <div id="popup-container">
            <div id="settings">
            </div>
        </div>
        <div id="portal-container"></div>
    </div>

    <!-- Main Content, With initial page -->
    <div id="search-container" class="vflex-center">
        <form id="search-form" class="hflex-vcenter" action="" method="post" autocomplete="on">
            <label id="search-label" for="search-bar"><?php echo translateLanguage("search.image") ?></label>
            <input id="search-bar" type="search" name="queryStr" value="<?php echo $queryStr; ?>">
            <label for="auto-fetch-details">Auto Fetch Details</label>
            <input id="auto-fetch-details" type="checkbox" name="autoFetchDetails" <?php if (!$hasSearched || $autoFetchDetails) echo 'checked'; ?>>
            <label for="filter-non-geo">Filter Non Geo</label>
            <input id="filter-non-geo" type="checkbox" name="filterNonGeo" <?php if (!$hasSearched || $filterNonGeo) echo 'checked'; ?>>
            <label for="translate-non-latin">Translate Non Latin</label>
            <input id="translate-non-latin" type="checkbox" name="translateNonLatin" <?php if (!$hasSearched || $translateNonLatin) echo 'checked'; ?>>
            <input id="toggle-layout" type="checkbox" name="toggleLayout"<?php if(!$hasSearched || $toggleLayout) echo 'checked' ?>>
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
            <input id="toggle-language" type="checkbox" name="toggleLanguage"<?php 
            if(!$hasSearched || $toggleLanguage) {
                echo 'checked'; 
                $_SESSION["currentLang"] = "sv";
            }
            elseif(!$hasSearched || !$toggleLanguage){
                $_SESSION["currentLang"] = "en";
            }
                ?>>
            <?php
                echoFilter(
                    [
                        "relevant" => "Relevance",
                        "latest" => "Latest"
                    ],
                    $orderBy
                );
            ?>
            <input id="search-button" type="submit" value="Search">
        </form>
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
        </div>

        <div class="reoderable-image-container php-endpoint-response">
            <?php
                if ($searchInfo) {
                    echo "<p id=\"search-info\">$searchInfo</p>";
                }

                if(empty($queryStr)){
                    return;
                }

                foreach ($images as $image) {
                    echoImageHTML($image, $translateNonLatin, $translator);
                }
            ?>
        </div>
    </main>
</body>
</html>