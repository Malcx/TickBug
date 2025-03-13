<?php

// ------------------------------------------------------------

// api/tickets/assign.php
// Assign ticket API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to assign a ticket.'];
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
$assignedTo = isset($_POST['assigned_to']) && !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;

// Validate form data
if (empty($ticketId)) {
    $response = ['success' => false, 'message' => 'Ticket ID is required.'];
    sendJsonResponse($response);
}

// Assign ticket
$result = assignTicket($ticketId, $assignedTo, $userId);

// Return response
sendJsonResponse($result);