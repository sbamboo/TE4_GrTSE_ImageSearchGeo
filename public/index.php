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
require_once('./php/components.php');

// Handle incomming search form POST, parsing out "queryStr" (string), "orderBy" (string:enum), "autoFetchDetails" (bool)
$queryStr = $_POST['queryStr'] ?? '';
$orderBy = $_POST['orderBy'] ?? 'relevant'; // "relevant" or "latest"
$autoFetchDetails = isset($_POST['autoFetchDetails']) ? ($_POST['autoFetchDetails'] === 'true' || $_POST['autoFetchDetails'] === '1') : false;

// Main page and perform the first search using the UnsplashAPI class
// Saving stuff a contextual data for the frontend to be able to send subsequent requests with
?>

<!DOCTYPE html>
<html lang="sv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="./css/main.css">
    
    <!-- Context Meta (Use already validated values) -->
    <meta name="queryStr" content="<?php echo htmlspecialchars($queryStr, ENT_QUOTES); ?>">
    <meta name="orderBy" content="<?php echo htmlspecialchars($orderBy, ENT_QUOTES); ?>">
    <meta name="autoFetchDetails" content="<?php echo $autoFetchDetails ? 'true' : 'false'; ?>">

    <title>Document</title>
</head>
<body>
    <!-- Main Content, With initial page -->
    <div id="search-container">
        <form id="search-form" action="" method="post" autocomplete="on">
            <label id="search-label" for="search-bar">Search image</label>
            <input id="search-bar" type="search" name="queryStr" value="<?php echo $queryStr; ?>">
            <input type="checkbox" id="autoFetchDetails" name="autoFetchDetails" value="true" <?php if ($autoFetchDetails) echo 'checked'; ?>>
            <?php
                echoFilter(
                    [
                        "relevant" => "Relevance",
                        "latest" => "Latest"
                    ],
                    $orderBy
                );
            ?>
            <input type="submit" value="Search">
        </form>
    </div>
    <div class="php-endpoint-response">
        <?php
            $unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY'], $autoFetchDetails);
            $images = $unsplash->SearchPhotos($queryStr, 10, 1);
            foreach ($images as $image) {
                echoImageHTML($image);
            }
        ?>
    </div>
</body>
</html>