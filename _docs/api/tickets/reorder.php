<?php
// api/tickets/reorder.php
// Enhanced reorder tickets API endpoint that supports priority grouping

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
$priority = isset($_POST['priority']) ? $_POST['priority'] : '';
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

// Get deliverable to check project ID
$deliverable = getDeliverable($deliverableId);

if (!$deliverable) {
    $response = ['success' => false, 'message' => 'Deliverable not found.'];
    sendJsonResponse($response);
}

$projectId = $deliverable['project_id'];

// Check if user has permission
$userRole = getUserProjectRole($userId, $projectId);

if (!$userRole || $userRole === 'Viewer' || $userRole === 'Tester') {
    $response = ['success' => false, 'message' => 'You do not have permission to reorder tickets.'];
    sendJsonResponse($response);
}

// Get database connection
$conn = getDbConnection();

// Start transaction
$conn->begin_transaction();

try {
    foreach ($order as $index => $ticketId) {
        // Update the display order of this ticket
        $stmt = $conn->prepare("
            UPDATE tickets 
            SET display_order = ?
            WHERE ticket_id = ? AND deliverable_id = ?
        ");
        $stmt->bind_param("iii", $index, $ticketId, $deliverableId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update ticket order: " . $conn->error);
        }
    }
    
    // Log activity
    if (LOG_ACTIONS) {
        logActivity($userId, $projectId, 'ticket', 0, 'reordered', [
            'deliverable_id' => $deliverableId,
            'priority' => $priority,
            'order' => $order
        ]);
    }
    
    // Commit transaction
    $conn->commit();
    
    $response = ['success' => true, 'message' => 'Tickets reordered successfully.'];
    sendJsonResponse($response);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $response = ['success' => false, 'message' => $e->getMessage()];
    sendJsonResponse($response);
}