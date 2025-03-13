<?php

// ------------------------------------------------------------

// api/projects/archive.php
// Archive project API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to archive a project.'];
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
$projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

// Validate form data
if (empty($projectId)) {
    $response = ['success' => false, 'message' => 'Project ID is required.'];
    sendJsonResponse($response);
}

// Archive project
$result = archiveProject($projectId, $userId);

// Return response
sendJsonResponse($result);
