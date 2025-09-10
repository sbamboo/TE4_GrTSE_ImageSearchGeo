<?php
require_once('./../../php/unsplash_api.php');


if(!empty($_POST["keyWord"])){
    $searchKeyWord = $_POST["keyWord"];

    $SECRETS = parse_ini_file(__DIR__ . '/../../../php_secrets.ini', false, INI_SCANNER_TYPED);


    $unsplash = new UnsplashAPI($SECRETS['UNSPLASH_ACCESS_KEY']);

    try {
        $random_photo = $unsplash->make_get_request('photos/random');
        echo '<pre>';
        print_r($random_photo);
        echo '</pre>';
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
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