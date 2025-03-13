<?php

// ------------------------------------------------------------

// api/deliverables/delete.php
// Delete deliverable API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to delete a deliverable.'];
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

// Validate form data
if (empty($deliverableId)) {
    $response = ['success' => false, 'message' => 'Deliverable ID is required.'];
    sendJsonResponse($response);
}

// Delete deliverable
$result = deleteDeliverable($deliverableId, $userId);

// Return response
sendJsonResponse($result);