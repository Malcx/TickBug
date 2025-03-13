<?php

// ------------------------------------------------------------

// api/files/upload.php
// File upload API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to upload files.'];
    sendJsonResponse($response);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = ['success' => false, 'message' => 'Invalid request method.'];
    sendJsonResponse($response);
}

// Get current user ID
$userId = getCurrentUserId();

// Check if file is uploaded
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    $response = ['success' => false, 'message' => 'No file uploaded or upload error.'];
    sendJsonResponse($response);
}

// Upload file
$result = uploadFile($_FILES['file'], $userId);

// Return response
sendJsonResponse($result);