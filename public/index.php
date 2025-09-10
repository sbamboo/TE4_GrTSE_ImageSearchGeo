<?php
// Get all the secrets from php.ini file
$SECRETS = parse_ini_file(__DIR__ . '/../php_secrets.ini', false, INI_SCANNER_TYPED); //This is not allowed to start with . or ..

// Imports
require_once('./php/unsplash_api.php');


if(!empty($_POST["keyWord"])){
    $searchKeyWord = $_POST["keyWord"];
    echo $searchKeyWord;
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <form action="" method="post" autocomplete="on">
        <h3>Search:</h3>
        <input type="text" name="keyWord" value="<?php echo $_POST['keyWord'] ?? ''; ?>">
    </form>
</body>
</html>