<?php
require_once('./../php/endpoint_helpers.php');

function fetchWithStatus(string $url, int $maxRedirects = 10): array {
    $redirectCount = 0;

    while ($redirectCount < $maxRedirects) {
        $context = stream_context_create([
            "http" => [
                "method" => "GET",
                "ignore_errors" => true // so body is always captured
            ]
        ]);

        $data = @file_get_contents($url, false, $context);

        if ($data === false || !isset($http_response_header[0])) {
            return [ 'data' => null, 'httpCode' => 0 ];
        }

        // Extract HTTP status code (e.g. "HTTP/1.1 200 OK")
        $parts = explode(' ', $http_response_header[0]);
        $httpCode = isset($parts[1]) ? (int)$parts[1] : 0;

        // Handle redirect (3xx)
        if ($httpCode >= 300 && $httpCode < 400) {
            $location = null;
            foreach ($http_response_header as $header) {
                if (stripos($header, 'Location:') === 0) {
                    $location = trim(substr($header, 9));
                    break;
                }
            }
            if (!$location) {
                return [ 'data' => null, 'httpCode' => $httpCode ];
            }
            $url = $location;
            $redirectCount++;
            continue;
        }

        return [ 'data' => $data, 'httpCode' => $httpCode ];
    }

    // Too many redirects
    return [ 'data' => null, 'httpCode' => 310 ]; // 310 = "Too many redirects"
}

// Endpoint logic
if (!isset($_GET['url']) || !isset($_GET['filename']) || !isset($_GET['filetype'])) {
    respondBadRequest("Missing parameters: 'url', 'filename' and 'filetype' are required.");
}

$url = $_GET['url'];
$file = rawurldecode($_GET['filename']) . '.' . rawurldecode($_GET['filetype']);

if (!filter_var($url, FILTER_VALIDATE_URL)) {
    respondBadRequest("Invalid URL.");
}

$result = fetchWithStatus($url);

if ($result['httpCode'] !== 200 || $result['data'] === null) {
    respondError(new Exception("Failed to fetch the image. HTTP Code: {$result['httpCode']}"));
}

setupHeadersFile(strlen($result['data']), $file);
echo $result['data'];
