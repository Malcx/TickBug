<?php
// includes/deliverables.php
// Deliverable related functions

/**
 * Create a new deliverable
 * 
 * @param int $projectId Project ID
 * @param string $name Deliverable name
 * @param string $description Deliverable description
 * @param int $userId User ID of creator
 * @return array Response with status and deliverable data
 */
function createDeliverable($projectId, $name, $description, $userId) {
    $conn = getDbConnection();
    
    // Validate input
    if (empty($name)) {
        return ['success' => false, 'message' => 'Deliverable name is required.'];
    }
    
    // Check if user has permission
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole || $userRole === 'Viewer' || $userRole === 'Tester') {
        return ['success' => false, 'message' => 'You do not have permission to create deliverables.'];
    }
    
    // Get the max display order for the project
    $stmt = $conn->prepare("
        SELECT MAX(display_order) as max_order
        FROM deliverables
        WHERE project_id = ?
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $displayOrder = ($row['max_order'] === null) ? 0 : $row['max_order'] + 1;
    
    // Insert deliverable
    $stmt = $conn->prepare("
        INSERT INTO deliverables (project_id, name, description, display_order, created_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issis", $projectId, $name, $description, $displayOrder, $userId);
    
    if ($stmt->execute()) {
        $deliverableId = $conn->insert_id;
        
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'deliverable', $deliverableId, 'created', [
                'name' => $name
            ]);
        }
        
        // Send notifications to project members
        notifyProjectMembers($projectId, $userId, 'deliverable_created', [
            'deliverable_id' => $deliverableId,
            'deliverable_name' => $name
        ]);
        
        return [
            'success' => true, 
            'message' => 'Deliverable created successfully.',
            'deliverable' => [
                'deliverable_id' => $deliverableId,
                'project_id' => $projectId,
                'name' => $name,
                'description' => $description,
                'display_order' => $displayOrder,
                'created_by' => $userId
            ]
        ];
    } else {
        return ['success' => false, 'message' => 'Failed to create deliverable: ' . $conn->error];
    }
}

/**
 * Get a deliverable by ID
 * 
 * @param int $deliverableId Deliverable ID
 * @return array|false Deliverable data or false if not found
 */
function getDeliverable($deliverableId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT d.*, u.first_name, u.last_name, p.name as project_name
        FROM deliverables d
        JOIN users u ON d.created_by = u.user_id
        JOIN projects p ON d.project_id = p.project_id
        WHERE d.deliverable_id = ?
    ");
    $stmt->bind_param("i", $deliverableId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $deliverable = $result->fetch_assoc();
    
    // Get tickets for this deliverable
    $deliverable['tickets'] = getDeliverableTickets($deliverableId);
    
    return $deliverable;
}

/**
 * Get all deliverables for a project
 * 
 * @param int $projectId Project ID
 * @return array Array of deliverables
 */
function getProjectDeliverables($projectId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT d.*, u.first_name, u.last_name
        FROM deliverables d
        JOIN users u ON d.created_by = u.user_id
        WHERE d.project_id = ?
        ORDER BY d.display_order
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $deliverables = [];
    
    while ($row = $result->fetch_assoc()) {
        // Get tickets for this deliverable
        $row['tickets'] = getDeliverableTickets($row['deliverable_id']);
        $deliverables[] = $row;
    }
    
    return $deliverables;
}

/**
 * Get all tickets for a deliverable
 * 
 * @param int $deliverableId Deliverable ID
 * @return array Array of tickets
 */
function getDeliverableTickets($deliverableId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT t.*, 
               creator.first_name as creator_first_name, 
               creator.last_name as creator_last_name,
               assignee.first_name as assignee_first_name, 
               assignee.last_name as assignee_last_name
        FROM tickets t
        JOIN users creator ON t.created_by = creator.user_id
        LEFT JOIN users assignee ON t.assigned_to = assignee.user_id
        WHERE t.deliverable_id = ?
        ORDER BY t.display_order
    ");
    $stmt->bind_param("i", $deliverableId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tickets = [];
    
    while ($row = $result->fetch_assoc()) {
        // Add assigned name for convenience
        if ($row['assigned_to']) {
            $row['assigned_name'] = $row['assignee_first_name'] . ' ' . $row['assignee_last_name'];
        } else {
            $row['assigned_name'] = '';
        }
        
        $tickets[] = $row;
    }
    
    return $tickets;
}

/**
 * Update a deliverable
 * 
 * @param int $deliverableId Deliverable ID
 * @param string $name Deliverable name
 * @param string $description Deliverable description
 * @param int $userId User ID making the update
 * @return array Response with status and message
 */
function updateDeliverable($deliverableId, $name, $description, $userId) {
    $conn = getDbConnection();
    
    // Validate input
    if (empty($name)) {
        return ['success' => false, 'message' => 'Deliverable name is required.'];
    }
    
    // Get deliverable to check project ID
    $deliverable = getDeliverable($deliverableId);
    
    if (!$deliverable) {
        return ['success' => false, 'message' => 'Deliverable not found.'];
    }
    
    $projectId = $deliverable['project_id'];
    
    // Check if user has permission
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole || $userRole === 'Viewer' || $userRole === 'Tester') {
        return ['success' => false, 'message' => 'You do not have permission to update deliverables.'];
    }
    
    // Update deliverable
    $stmt = $conn->prepare("UPDATE deliverables SET name = ?, description = ? WHERE deliverable_id = ?");
    $stmt->bind_param("ssi", $name, $description, $deliverableId);
    
    if ($stmt->execute()) {
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'deliverable', $deliverableId, 'updated', [
                'name' => $name
            ]);
        }
        
        return ['success' => true, 'message' => 'Deliverable updated successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to update deliverable: ' . $conn->error];
    }
}

/**
 * Delete a deliverable
 * 
 * @param int $deliverableId Deliverable ID
 * @param int $userId User ID making the deletion
 * @return array Response with status and message
 */
function deleteDeliverable($deliverableId, $userId) {
    $conn = getDbConnection();
    
    // Get deliverable to check project ID and get tickets
    $deliverable = getDeliverable($deliverableId);
    
    if (!$deliverable) {
        return ['success' => false, 'message' => 'Deliverable not found.'];
    }
    
    $projectId = $deliverable['project_id'];
    
    // Check if user has permission
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole || $userRole === 'Viewer' || $userRole === 'Tester' || 
        ($userRole !== 'Owner' && $userRole !== 'Project Manager')) {
        return ['success' => false, 'message' => 'You do not have permission to delete deliverables.'];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get all tickets for the deliverable
        $tickets = getDeliverableTickets($deliverableId);
        
        // Delete all comments and files related to these tickets
        foreach ($tickets as $ticket) {
            $ticketId = $ticket['ticket_id'];
            
            // Delete ticket files
            $stmt = $conn->prepare("
                DELETE f FROM files f 
                JOIN ticket_files tf ON f.file_id = tf.file_id 
                WHERE tf.ticket_id = ?
            ");
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
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete deliverable: " . $conn->error);
        }
        
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'deliverable', $deliverableId, 'deleted', [
                'name' => $deliverable['name']
            ]);
        }
        
        // Commit transaction
        $conn->commit();
        
        return ['success' => true, 'message' => 'Deliverable deleted successfully.'];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Reorder deliverables
 * 
 * @param array $order Array of deliverable IDs in the new order
 * @param int $userId User ID making the reorder
 * @return array Response with status and message
 */
function reorderDeliverables($order, $userId) {
    $conn = getDbConnection();
    
    if (empty($order)) {
        return ['success' => false, 'message' => 'Order is required.'];
    }
    
    // Get the first deliverable to check project ID
    $deliverableId = $order[0];
    $deliverable = getDeliverable($deliverableId);
    
    if (!$deliverable) {
        return ['success' => false, 'message' => 'Deliverable not found.'];
    }
    
    $projectId = $deliverable['project_id'];
    
    // Check if user has permission
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole || $userRole === 'Viewer' || $userRole === 'Tester') {
        return ['success' => false, 'message' => 'You do not have permission to reorder deliverables.'];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        foreach ($order as $index => $deliverableId) {
            $stmt = $conn->prepare("
                UPDATE deliverables 
                SET display_order = ?
                WHERE deliverable_id = ? AND project_id = ?
            ");
            $stmt->bind_param("iii", $index, $deliverableId, $projectId);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to update deliverable order: " . $conn->error);
            }
        }
        
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'deliverable', 0, 'reordered', [
                'order' => $order
            ]);
        }
        
        // Commit transaction
        $conn->commit();
        
        return ['success' => true, 'message' => 'Deliverables reordered successfully.'];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}