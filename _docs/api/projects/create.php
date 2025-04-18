<?php

// api/projects/create.php
// Create project API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to create a project.'];
    sendJsonResponse($response);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = ['success' => false, 'message' => 'Invalid request method.'];
    sendJsonResponse($response);
}

// Get current user ID
$userId = getCurrentUserId();

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$themeColor = isset($_POST['theme_color']) ? trim($_POST['theme_color']) : '#20205E';

// Validate form data
if (empty($name)) {
    $response = ['success' => false, 'message' => 'Project name is required.'];
    sendJsonResponse($response);
}

// Create project
$result = createProject($name, $description, $themeColor, $userId);

// Return response
sendJsonResponse($result);