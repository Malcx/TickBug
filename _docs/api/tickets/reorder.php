<?php

// ------------------------------------------------------------

// api/tickets/reorder.php
// Reorder tickets API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to reorder tickets.'];
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
$order = isset($_POST['order']) ? $_POST['order'] : [];

// Validate form data
if (empty($deliverableId)) {
    $response = ['success' => false, 'message' => 'Deliverable ID is required.'];
    sendJsonResponse($response);
}

if (empty($order) || !is_array($order)) {
    $response = ['success' => false, 'message' => 'Order is required and must be an array.'];
    sendJsonResponse($response);
}

// Convert order values to integers
$order = array_map('intval', $order);

// Reorder tickets
$result = reorderTickets($deliverableId, $order, $userId);

// Return response
sendJsonResponse($result);