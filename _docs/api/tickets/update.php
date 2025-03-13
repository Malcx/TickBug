<?php

// ------------------------------------------------------------

// api/tickets/update.php
// Update ticket API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to update a ticket.'];
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
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$url = isset($_POST['url']) ? trim($_POST['url']) : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';
$priority = isset($_POST['priority']) ? $_POST['priority'] : '';
$assignedTo = isset($_POST['assigned_to']) && !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;

// Check for move action (change deliverable)
$action = isset($_POST['action']) ? $_POST['action'] : '';
$deliverableId = isset($_POST['deliverable_id']) ? (int)$_POST['deliverable_id'] : 0;

// Validate form data
if (empty($ticketId)) {
    $response = ['success' => false, 'message' => 'Ticket ID is required.'];
    sendJsonResponse($response);
}

// Get ticket info
$ticket = getTicket($ticketId);

if (!$ticket) {
    $response = ['success' => false, 'message' => 'Ticket not found.'];
    sendJsonResponse($response);
}

// Check if user has permission
$projectId = $ticket['project_id'];
$userRole = getUserProjectRole($userId, $projectId);

if (!$userRole || $userRole === 'Viewer' || 
    ($userRole === 'Tester' && $ticket['created_by'] !== $userId)) {
    $response = ['success' => false, 'message' => 'You do not have permission to update this ticket.'];
    sendJsonResponse($response);
}

// Handle special action: move to different deliverable
if ($action === 'move' && $deliverableId > 0) {
    // Get deliverable info
    $deliverable = getDeliverable($deliverableId);
    
    if (!$deliverable) {
        $response = ['success' => false, 'message' => 'Target deliverable not found.'];
        sendJsonResponse($response);
    }
    
    // Check if deliverable is in the same project
    if ($deliverable['project_id'] !== $projectId) {
        $response = ['success' => false, 'message' => 'Cannot move ticket to a deliverable in a different project.'];
        sendJsonResponse($response);
    }
    
    // Update ticket's deliverable
    $conn = getDbConnection();
    $stmt = $conn->prepare("UPDATE tickets SET deliverable_id = ? WHERE ticket_id = ?");
    $stmt->bind_param("ii", $deliverableId, $ticketId);
    
    if ($stmt->execute()) {
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'ticket', $ticketId, 'moved', [
                'from_deliverable' => $ticket['deliverable_id'],
                'to_deliverable' => $deliverableId
            ]);
        }
        
        $response = ['success' => true, 'message' => 'Ticket moved successfully.'];
        sendJsonResponse($response);
    } else {
        $response = ['success' => false, 'message' => 'Failed to move ticket: ' . $conn->error];
        sendJsonResponse($response);
    }
}

// Handle file uploads
$files = [];
if (isset($_FILES['files']) && is_array($_FILES['files']['name'])) {
    for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
        if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
            $file = [
                'name' => $_FILES['files']['name'][$i],
                'type' => $_FILES['files']['type'][$i],
                'tmp_name' => $_FILES['files']['tmp_name'][$i],
                'error' => $_FILES['files']['error'][$i],
                'size' => $_FILES['files']['size'][$i]
            ];
            $files[] = $file;
        }
    }
}

// Update ticket
$result = updateTicket($ticketId, $title, $description, $url, $status, $priority, $assignedTo, $userId, $files);

// Return response
sendJsonResponse($result);