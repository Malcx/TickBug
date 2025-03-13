<?php

// ------------------------------------------------------------

// uploads.php
// File download handler

// Include helper functions
require_once 'includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    // Return 403 Forbidden
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Get current user ID
$userId = getCurrentUserId();

// Check if file ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    // Return 400 Bad Request
    header('HTTP/1.1 400 Bad Request');
    exit('File ID is required');
}

$fileId = (int)$_GET['id'];

// Check if user has access to the file
if (!canAccessFile($fileId, $userId)) {
    // Return 403 Forbidden
    header('HTTP/1.1 403 Forbidden');
    exit('You do not have permission to access this file');
}

// Get file information
$file = getFile($fileId);

if (!$file) {
    // Return 404 Not Found
    header('HTTP/1.1 404 Not Found');
    exit('File not found');
}

// Set file path
$filePath = ROOT_PATH . $file['filepath'];

// Check if file exists
if (!file_exists($filePath)) {
    // Return 404 Not Found
    header('HTTP/1.1 404 Not Found');
    exit('File not found on server');
}

// Set appropriate headers
header('Content-Type: ' . $file['filetype']);
header('Content-Disposition: inline; filename="' . $file['filename'] . '"');
header('Content-Length: ' . $file['filesize']);

// Output file contents
readfile($filePath);
exit;