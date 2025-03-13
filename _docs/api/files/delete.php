<?php

// ------------------------------------------------------------

// api/files/delete.php
// Delete file API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to delete files.'];
    sendJsonResponse($response);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = ['success' => false, 'message' => 'Invalid request method.'];
    sendJsonResponse($response);
}

// Get current user ID
$userId = getCurrentUserId();

// Get form data
$fileId = isset($_POST['file_id']) ? (int)$_POST['file_id'] : 0;

// Validate form data
if (empty($fileId)) {
    $response = ['success' => false, 'message' => 'File ID is required.'];
    sendJsonResponse($response);
}

// Delete file
$result = deleteFile($fileId, $userId);

// Return response
sendJsonResponse($result);