<?php

require_once __DIR__ . '/vendor/autoload.php';

use LibreMailApi\LibreMailApi;

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers for CORS if needed
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Initialize LibreMailApi
    $libreMailApi = new LibreMailApi();

    // Handle the request
    $libreMailApi->handleRequest();
    
} catch (Exception $e) {
    // Handle any uncaught exceptions
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ], JSON_PRETTY_PRINT);
}
