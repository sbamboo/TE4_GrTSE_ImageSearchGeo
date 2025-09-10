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

if(!empty($_POST["query"])){
    $query = $_POST["query"];
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/main.css">
    <script src="main.js"></script>
    <title>Document</title>
</head>
<body>
    <div id="search-container">
        <form id="search-form" action="unsplash_api.php" method="post" autocomplete="on">
            <label id="search-label" for="search-bar">Search image</label>
            <input id="search-bar" type="search" name="query" value="<?php echo $_POST['query'] ?? ''; ?>">
            <input type="submit" value="Search">
        </form>
    </div>
</body>
</html>