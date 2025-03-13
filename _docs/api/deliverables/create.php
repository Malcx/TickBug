<?php

// api/deliverables/create.php
// Create deliverable API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to create a deliverable.'];
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

// Validate form data
if (empty($projectId)) {
    $response = ['success' => false, 'message' => 'Project ID is required.'];
    sendJsonResponse($response);
}

if (empty($name)) {
    $response = ['success' => false, 'message' => 'Deliverable name is required.'];
    sendJsonResponse($response);
}

// Check if user has permission
$userRole = getUserProjectRole($userId, $projectId);

if (!$userRole || $userRole === 'Viewer' || $userRole === 'Tester') {
    $response = ['success' => false, 'message' => 'You do not have permission to create deliverables.'];
    sendJsonResponse($response);
}

// Create deliverable
$result = createDeliverable($projectId, $name, $description, $userId);

// Return response
sendJsonResponse($result);