<?php
// Setup enviroment
//ini_set('log_errors', 1);
//ini_set('error_log', './../php-errors.log');
//ini_set('display_errors', 0); // prevent sending to stderr

// Get all the secrets from php.ini file
$SECRETS = parse_ini_file(__DIR__ . '/../php_secrets.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..

// Imports
require_once('./php/libs/blurhash.php');
require_once('./php/unsplash_api.php');
require_once('./php/translate.php');
require_once('./php/components.php');

// Instantiate translator
$translator = new GTranslate($SECRETS['GTRANSLATE_API_KEY']);

// Handle incomming search form POST, parsing out "queryStr" (string), "orderBy" (string:enum), "autoFetchDetails" (bool)
$queryStr = $_POST['queryStr'] ?? '';
$orderBy = $_POST['orderBy'] ?? 'relevant'; // "relevant" or "latest"
$autoFetchDetails = isset($_POST['autoFetchDetails']);
$filterNonGeo = isset($_POST['filterNonGeo']);
$translateNonLatin = isset($_POST['translateNonLatin']);

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
        //$images = $unsplash->SearchPhotos($queryStr, 10, 2, $filterNonGeo, $orderBy);
    }
}
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/main.css">
    
    <script src="./js/main.js"></script>
    
    <!-- Context Meta (Use already validated values) -->
    <meta name="queryStr" content="<?php echo htmlspecialchars($queryStr, ENT_QUOTES); ?>">
    <meta name="orderBy" content="<?php echo htmlspecialchars($orderBy, ENT_QUOTES); ?>">
    <meta name="autoFetchDetails" content="<?php echo $autoFetchDetails ? 'true' : 'false'; ?>">
    <meta name="filterNonGeo" content="<?php echo $filterNonGeo ? 'true' : 'false'; ?>">
    <meta name="translateNonLatin" content="<?php echo $translateNonLatin ? 'true' : 'false'; ?>">
    <meta name="pageNr" content="<?php echo $pageNr ?>">

    <title>Document</title>
</head>
<body>
    <!-- Main Content, With initial page -->
    <div id="search-container">
        <form id="search-form" action="" method="post" autocomplete="on">
            <label id="search-label" for="search-bar">Search image</label>
            <input id="search-bar" type="search" name="queryStr" value="<?php echo $queryStr; ?>">
            <label for="auto-fetch-details">Auto Fetch Details</label>
            <input type="checkbox" id="auto-fetch-details" name="autoFetchDetails" <?php if ($hasSearched && $autoFetchDetails) echo 'checked'; ?>>
            <label for="filter-non-geo">Filter Non Geo</label>
            <input type="checkbox" id="filter-non-geo" name="filterNonGeo" <?php if (!$hasSearched || $filterNonGeo) echo 'checked'; ?>>
            <label for="translate-non-latin">Translate Non Latin</label>
            <input type="checkbox" id="translate-non-latin" name="translateNonLatin" <?php if (!$hasSearched || $translateNonLatin) echo 'checked'; ?>>
            <?php
                echoFilter(
                    [
                        "relevant" => "Relevance",
                        "latest" => "Latest"
                    ],
                    $orderBy
                );
            ?>
            <input type="submit" id="search-button" value="Search">
        </form>
    </div>
    <div class="php-endpoint-response">
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
</body>
</html>