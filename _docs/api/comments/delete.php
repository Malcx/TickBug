<?php


// ------------------------------------------------------------

// api/comments/delete.php
// Delete comment API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to delete a comment.'];
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
$commentId = isset($_POST['comment_id']) ? (int)$_POST['comment_id'] : 0;

// Validate form data
if (empty($commentId)) {
    $response = ['success' => false, 'message' => 'Comment ID is required.'];
    sendJsonResponse($response);
}

// Delete comment
$result = deleteComment($commentId, $userId);

// Return response
sendJsonResponse($result);