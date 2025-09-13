<?php
// This file contains helpers for communication made over endpoints

function setupHeaders() {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, OPTIONS'); // Allow specific HTTP methods
}

function setupHeadersJSON() {
    header('Content-Type: application/json');
    setupHeaders();
}

function setupHeadersHTML() {
    header('Content-Type: text/html; charset=UTF-8');
    setupHeaders();
}

function setupHeadersFile(int $len, string $filename) {
    header('Content-Type: application/octet-stream');
    header("Content-Disposition: attachment; filename=\"{$filename}\"");
    header("Content-Length: " . $len);
    setupHeaders();
}

function respondOK(string $message = "") {
    http_response_code(200); // OK
    echo json_encode(['status' => 'success', 'message' => $message]);
    exit();
}

function respondBadRequest(string $message = "Invalid request") {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Bad Request', 'message' => $message]);
    exit();
}

function respondError(Throwable $phpCatchedError) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'An error occurred', 'message' => $phpCatchedError->getMessage()]);
    exit();
}

function getUrlParameters(): array {
    // Get URL parameters
    $params = [];
    if (isset($_SERVER['QUERY_STRING'])) {
        parse_str($_SERVER['QUERY_STRING'], $params);
    }
    return $params;
}

function respondOKContent(array $content) {
    http_response_code(200); // OK
    echo json_encode($content);
    exit();
}

?>