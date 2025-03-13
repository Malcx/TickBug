<?php

// ------------------------------------------------------------

// api/tickets/change-status.php
// Change ticket status API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to change ticket status.'];
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
$ticketId = isset($_POST['ticket_id']) ? (int)$_POST['ticket_id'] : 0;
$status = isset($_POST['status']) ? $_POST['status'] : '';

// Validate form data
if (empty($ticketId)) {
    $response = ['success' => false, 'message' => 'Ticket ID is required.'];
    sendJsonResponse($response);
}

if (empty($status)) {
    $response = ['success' => false, 'message' => 'Status is required.'];
    sendJsonResponse($response);
}

// Change ticket status
$result = changeTicketStatus($ticketId, $status, $userId);

// Return response
sendJsonResponse($result);
