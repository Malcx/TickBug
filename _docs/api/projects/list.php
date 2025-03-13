<?php

// ------------------------------------------------------------

// api/projects/list.php
// List projects API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to view projects.'];
    sendJsonResponse($response);
}

// Get current user ID
$userId = getCurrentUserId();

// Get archived flag
$archived = isset($_GET['archived']) && $_GET['archived'] === 'true';

// Get projects
if ($archived) {
    $projects = getUserArchivedProjects($userId);
} else {
    $projects = getUserProjects($userId);
}

// Return response
$response = [
    'success' => true,
    'projects' => $projects
];

sendJsonResponse($response);
