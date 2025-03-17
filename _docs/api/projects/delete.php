<?php
// ------------------------------------------------------------

// api/projects/delete.php
// Delete project API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to delete a project.'];
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
$projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

// Validate form data
if (empty($projectId)) {
    $response = ['success' => false, 'message' => 'Project ID is required.'];
    sendJsonResponse($response);
}

// Check if user has permission to delete project
$userRole = getUserProjectRole($userId, $projectId);

if (!$userRole || $userRole !== 'Owner') {
    $response = ['success' => false, 'message' => 'Only the project owner can delete a project.'];
    sendJsonResponse($response);
}

// Delete project
$conn = getDbConnection();

// Start transaction
$conn->begin_transaction();

try {
    // Get all deliverables for the project
    $stmt = $conn->prepare("SELECT deliverable_id FROM deliverables WHERE project_id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $deliverablesResult = $stmt->get_result();
    
    while ($deliverable = $deliverablesResult->fetch_assoc()) {
        $deliverableId = $deliverable['deliverable_id'];
        
        // Get all tickets for the deliverable
        $stmt = $conn->prepare("SELECT ticket_id FROM tickets WHERE deliverable_id = ?");
        $stmt->bind_param("i", $deliverableId);
        $stmt->execute();
        $ticketsResult = $stmt->get_result();
        
        while ($ticket = $ticketsResult->fetch_assoc()) {
            $ticketId = $ticket['ticket_id'];
            
            // Delete ticket files
            $stmt = $conn->prepare("
                DELETE f FROM files f 
                JOIN ticket_files tf ON f.file_id = tf.file_id 
                WHERE tf.ticket_id = ?
            ");
            $stmt->bind_param("i", $ticketId);
            $stmt->execute();
            
            // Delete ticket_files references
            $stmt = $conn->prepare("DELETE FROM ticket_files WHERE ticket_id = ?");
            $stmt->bind_param("i", $ticketId);
            $stmt->execute();
            
            // Delete comment files
            $stmt = $conn->prepare("
                DELETE f FROM files f 
                JOIN comment_files cf ON f.file_id = cf.file_id 
                JOIN comments c ON cf.comment_id = c.comment_id 
                WHERE c.ticket_id = ?
            ");
            $stmt->bind_param("i", $ticketId);
            $stmt->execute();
            
            // Delete comment_files references
            $stmt = $conn->prepare("
                DELETE cf FROM comment_files cf
                JOIN comments c ON cf.comment_id = c.comment_id
                WHERE c.ticket_id = ?
            ");
            $stmt->bind_param("i", $ticketId);
            $stmt->execute();
            
            // Delete comments
            $stmt = $conn->prepare("DELETE FROM comments WHERE ticket_id = ?");
            $stmt->bind_param("i", $ticketId);
            $stmt->execute();
            
            // Delete ticket
            $stmt = $conn->prepare("DELETE FROM tickets WHERE ticket_id = ?");
            $stmt->bind_param("i", $ticketId);
            $stmt->execute();
        }
        
        // Delete deliverable
        $stmt = $conn->prepare("DELETE FROM deliverables WHERE deliverable_id = ?");
        $stmt->bind_param("i", $deliverableId);
        $stmt->execute();
    }
    
    // Delete activity log
    $stmt = $conn->prepare("DELETE FROM activity_log WHERE project_id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    
    // Delete project users
    $stmt = $conn->prepare("DELETE FROM project_users WHERE project_id = ?");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    
    // Delete project
    $stmt = $conn->prepare("DELETE FROM projects WHERE project_id = ?");
    $stmt->bind_param("i", $projectId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete project: " . $conn->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    $response = ['success' => true, 'message' => 'Project deleted successfully.'];
    sendJsonResponse($response);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $response = ['success' => false, 'message' => $e->getMessage()];
    sendJsonResponse($response);
}