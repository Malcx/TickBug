<?php
// ------------------------------------------------------------

// api/deliverables/update.php
// Update deliverable API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to update a deliverable.'];
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
$deliverableId = isset($_POST['deliverable_id']) ? (int)$_POST['deliverable_id'] : 0;
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';

// Validate form data
if (empty($deliverableId)) {
    $response = ['success' => false, 'message' => 'Deliverable ID is required.'];
    sendJsonResponse($response);
}

if (empty($name)) {
    $response = ['success' => false, 'message' => 'Deliverable name is required.'];
    sendJsonResponse($response);
}

// Update deliverable
$result = updateDeliverable($deliverableId, $name, $description, $userId);

// Return response
sendJsonResponse($result);