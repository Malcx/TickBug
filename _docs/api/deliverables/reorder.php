<?php

// ------------------------------------------------------------

// api/deliverables/reorder.php
// Reorder deliverables API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to reorder deliverables.'];
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
$order = isset($_POST['order']) ? $_POST['order'] : [];

// Validate form data
if (empty($order) || !is_array($order)) {
    $response = ['success' => false, 'message' => 'Order is required and must be an array.'];
    sendJsonResponse($response);
}

// Convert order values to integers
$order = array_map('intval', $order);

// Reorder deliverables
$result = reorderDeliverables($order, $userId);

// Return response
sendJsonResponse($result);