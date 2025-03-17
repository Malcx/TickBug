<?php

// api/tickets/create.php
// Create ticket API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to create a ticket.'];
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
$title = isset($_POST['title']) ? trim($_POST['title']) : '';
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$url = isset($_POST['url']) ? trim($_POST['url']) : '';
$statusId = isset($_POST['status_id']) ? (int)$_POST['status_id'] : 1; // Default to "New"
$priorityId = isset($_POST['priority_id']) ? (int)$_POST['priority_id'] : 3; // Default to "3 - Nice to have"
$assignedTo = isset($_POST['assigned_to']) && !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;

// Validate form data
if (empty($deliverableId)) {
    $response = ['success' => false, 'message' => 'Deliverable ID is required.'];
    sendJsonResponse($response);
}

if (empty($title)) {
    $response = ['success' => false, 'message' => 'Ticket title is required.'];
    sendJsonResponse($response);
}

// Check if deliverable exists and user has permission
$deliverable = getDeliverable($deliverableId);

if (!$deliverable) {
    $response = ['success' => false, 'message' => 'Deliverable not found.'];
    sendJsonResponse($response);
}

$projectId = $deliverable['project_id'];
$userRole = getUserProjectRole($userId, $projectId);

if (!$userRole || $userRole === 'Viewer') {
    $response = ['success' => false, 'message' => 'You do not have permission to create tickets.'];
    sendJsonResponse($response);
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

// Create ticket
$result = createTicket($deliverableId, $title, $description, $url, $status, $priority, $assignedTo, $userId, $files);

// Return response
sendJsonResponse($result);