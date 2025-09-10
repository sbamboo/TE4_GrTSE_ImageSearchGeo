<?php
$SECRETS = parse_ini_file(__DIR__ . '/../../../php_secrets.ini', false, INI_SCANNER_TYPED); // Replace with your Unsplash API Access Key

// Endpoint: random photo
$url = "https://api.unsplash.com/photos/random?client_id=" . $SECRETS['unsplash_access_key'];

// Initialize cURL
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if ($response === false) {
    echo "cURL error: " . curl_error($ch) . PHP_EOL;
}
var_dump($response);
curl_close($ch);

$data = json_decode($response, true);

if (isset($data["id"])) {
    echo "Photo ID: " . $data["id"] . PHP_EOL;
    echo "Photographer: " . $data["user"]["name"] . PHP_EOL;
    echo "Image URL: " . $data["urls"]["regular"] . PHP_EOL;

    if (!empty($data["location"])) {
        echo "\n--- Location Info ---" . PHP_EOL;
        echo "Title: " . ($data["location"]["title"] ?? "N/A") . PHP_EOL;
        echo "City: " . ($data["location"]["city"] ?? "N/A") . PHP_EOL;
        echo "Country: " . ($data["location"]["country"] ?? "N/A") . PHP_EOL;
        echo "Coordinates: " .
             ($data["location"]["position"]["latitude"] ?? "N/A") . ", " .
             ($data["location"]["position"]["longitude"] ?? "N/A") . PHP_EOL;
    } else {
        echo "No geodata available for this photo." . PHP_EOL;
    }
} else {
    echo "Error: " . $response . PHP_EOL;
}

?>