<?php
// This is an endpoint and returns file

// downloadProxy.php?url=<Unsplash-download-url>&filename=<filename>&filetype=jpg

// Imports
require_once('./../php/endpoint_helpers.php');

// Parse paramerters
if (!isset($_GET['url']) || !isset($_GET['filename']) || !isset($_GET['filetype'])) {
    respondBadRequest("Missing parameters: 'url', 'filename' and 'filetype' are required.");
}
$url = $_GET['url'];
$file = rawurldecode($_GET['filename']) . '.' . rawurldecode($_GET['filetype']);

// Fetch the remote image
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // CURLOPT_RETURNTRANSFER : return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // CURLOPT_FOLLOWLOCATION : follow redirects
$data = curl_exec($ch);

if (curl_errno($ch)) {
    respondError(new Exception(curl_error($ch)));
}
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE); // CURLINFO_HTTP_CODE : get the last received HTTP code
curl_close($ch);

if ($httpCode !== 200 || !$data) {
    respondError(new Exception("Failed to fetch the image. HTTP Code: $httpCode"));
}

// Serve it to the browser with download headers
setupHeadersFile(strlen($data), $file);
echo $data;
