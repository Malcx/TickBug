<?php
// api/projects/reorder.php
// Reorder projects API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to reorder projects.'];
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

// Start transaction
$conn = getDbConnection();
$conn->begin_transaction();

try {
    foreach ($order as $index => $projectId) {
        $stmt = $conn->prepare("
            UPDATE project_users 
            SET display_order = ?
            WHERE project_id = ? AND user_id = ?
        ");
        $stmt->bind_param("iii", $index, $projectId, $userId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update project order: " . $conn->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    $response = ['success' => true, 'message' => 'Projects reordered successfully.'];
    sendJsonResponse($response);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $response = ['success' => false, 'message' => $e->getMessage()];
    sendJsonResponse($response);
}