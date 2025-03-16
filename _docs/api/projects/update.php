<?php



// ------------------------------------------------------------

// api/projects/update.php
// Update project API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to update a project.'];
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
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$themeColor = isset($_POST['theme_color']) ? trim($_POST['theme_color']) : '#201E5B';

// Validate form data
if (empty($projectId)) {
    $response = ['success' => false, 'message' => 'Project ID is required.'];
    sendJsonResponse($response);
}

if (empty($name)) {
    $response = ['success' => false, 'message' => 'Project name is required.'];
    sendJsonResponse($response);
}

// Update project
$result = updateProject($projectId, $name, $description, $themeColor, $userId);

// Return response
sendJsonResponse($result);
