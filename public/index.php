<?php
// Setup enviroment
//ini_set('log_errors', 1);
//ini_set('error_log', './../php-errors.log');
//ini_set('display_errors', 0); // prevent sending to stderr

// Get all the secrets from php.ini file
$SECRETS = parse_ini_file(__DIR__ . '/../php_secrets.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..

// Imports
// require_once('./php/blurhash.php');
// require_once('./php/unsplash_api.php');

if(!empty($_POST["query"])){
    $query = $_POST["query"];
    echo $query;
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/main.css">
    <title>Document</title>
</head>
<body>
    <div id="search-container">
        <form id="search-form" action="" method="post" autocomplete="on">
            <h3 id="search-header">Search:</h3>
            <input id="search-bar" type="text" name="query" value="<?php echo $_POST['query'] ?? ''; ?>">
        </form>
    </div>
</body>
</html>