<?php
// ------------------------------------------------------------

// api/comments/update.php
// Update comment API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to update a comment.'];
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
$description = isset($_POST['description']) ? trim($_POST['description']) : '';
$url = isset($_POST['url']) ? trim($_POST['url']) : '';

// Validate form data
if (empty($commentId)) {
    $response = ['success' => false, 'message' => 'Comment ID is required.'];
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

// Update comment
$result = updateComment($commentId, $description, $url, $userId, $files);

// Return response
sendJsonResponse($result);