<?php
// includes/tickets.php
// Ticket related functions

/**
 * Create a new ticket
 * 
 * @param int $deliverableId Deliverable ID
 * @param string $title Ticket title
 * @param string $description Ticket description
 * @param string $url Ticket URL
 * @param string $status Ticket status
 * @param string $priority Ticket priority
 * @param int|null $assignedTo User ID assigned to ticket (optional)
 * @param int $userId User ID of creator
 * @param array $files Array of uploaded files (optional)
 * @return array Response with status and ticket data
 */
function createTicket($deliverableId, $title, $description, $url, $statusId, $priorityId, $assignedTo, $userId, $files = []) {
    $conn = getDbConnection();
    
    // Validate input
    if (empty($title)) {
        return ['success' => false, 'message' => 'Ticket title is required.'];
    }
    
    // Get deliverable to check project ID
    $deliverable = getDeliverable($deliverableId);
    
    if (!$deliverable) {
        return ['success' => false, 'message' => 'Deliverable not found.'];
    }
    
    $projectId = $deliverable['project_id'];
    
    // Check if user has permission (even testers can create tickets)
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole || $userRole === 'Viewer') {
        return ['success' => false, 'message' => 'You do not have permission to create tickets.'];
    }
    
    // If assignedTo is provided, check if user exists and is part of the project
    if ($assignedTo) {
        $assigneeRole = getUserProjectRole($assignedTo, $projectId);
        
        if (!$assigneeRole) {
            return ['success' => false, 'message' => 'Assigned user is not part of this project.'];
        }
    }
    
    // Get the max display order for the deliverable
    $stmt = $conn->prepare("
        SELECT MAX(display_order) as max_order
        FROM tickets
        WHERE deliverable_id = ?
    ");
    $stmt->bind_param("i", $deliverableId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $displayOrder = ($row['max_order'] === null) ? 0 : $row['max_order'] + 1;
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert ticket
        $stmt = $conn->prepare("
            INSERT INTO tickets (
                deliverable_id, title, description, url, status_id, priority_id, 
                assigned_to, created_by, display_order
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isssiiiis", 
            $deliverableId, $title, $description, $url, $statusId, $priorityId,
            $assignedTo, $userId, $displayOrder
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create ticket: " . $conn->error);
        }
        
        $ticketId = $conn->insert_id;
        
        // Process file uploads if any
        $uploadedFileIds = [];
        
        if (!empty($files) && is_array($files)) {
            foreach ($files as $file) {
                $fileData = uploadFile($file, $userId);
                
                if ($fileData['success']) {
                    $fileId = $fileData['file_id'];
                    $uploadedFileIds[] = $fileId;
                    
                    // Link file to ticket
                    $stmt = $conn->prepare("INSERT INTO ticket_files (ticket_id, file_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $ticketId, $fileId);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to link file to ticket: " . $conn->error);
                    }
                }
            }
        }
        
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'ticket', $ticketId, 'created', [
                'title' => $title,
                'status_id' => $statusId,
                'priority_id' => $priorityId,
                'assigned_to' => $assignedTo
            ]);
        }
        
        // If assigned to someone, send notification
        if ($assignedTo && $assignedTo != $userId) {
            $assigneePrefs = getProjectNotificationPreferences($projectId, $assignedTo);
            
            if ($assigneePrefs && $assigneePrefs['ticket_assigned']) {
                $assigneeInfo = getUserById($assignedTo);
                $project = getProject($projectId);
                
                if ($assigneeInfo) {
                    $notificationHtml = '
                        <h2>Ticket Assignment</h2>
                        <p>You have been assigned a new ticket in the project "' . htmlspecialchars($project['name']) . '".</p>
                        <p><strong>Ticket:</strong> ' . htmlspecialchars($title) . '</p>
                        <p><strong>Priority:</strong> ' . $priority . '</p>
                        <p>Please log in to view and update this ticket.</p>
                    ';
                    
                    $notificationText = "Ticket Assignment\n\n" .
                                   "You have been assigned a new ticket in the project \"" . $project['name'] . "\".\n\n" .
                                   "Ticket: " . $title . "\n" .
                                   "Priority: " . $priority . "\n\n" .
                                   "Please log in to view and update this ticket.";
                    
                    sendEmail($assigneeInfo['email'], 'Ticket Assignment: ' . $title, $notificationHtml, $notificationText);
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true, 
            'message' => 'Ticket created successfully.',
            'ticket' => [
                'ticket_id' => $ticketId,
                'deliverable_id' => $deliverableId,
                'title' => $title,
                'description' => $description,
                'url' => $url,
                'status' => $status,
                'priority' => $priority,
                'assigned_to' => $assignedTo,
                'created_by' => $userId,
                'display_order' => $displayOrder,
                'files' => $uploadedFileIds
            ]
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get a ticket by ID
 * 
 * @param int $ticketId Ticket ID
 * @return array|false Ticket data or false if not found
 */
function getTicket($ticketId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT t.*, 
               d.project_id,
               creator.first_name as creator_first_name, 
               creator.last_name as creator_last_name,
               assignee.first_name as assignee_first_name, 
               assignee.last_name as assignee_last_name,
               p.name as priority_name,
               s.name as status_name
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        JOIN users creator ON t.created_by = creator.user_id
        LEFT JOIN users assignee ON t.assigned_to = assignee.user_id
        LEFT JOIN priorities p ON t.priority_id = p.priority_id
        LEFT JOIN statuses s ON t.status_id = s.status_id
        WHERE t.ticket_id = ?
    ");
    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $ticket = $result->fetch_assoc();
    
    // Add assigned name for convenience
    if ($ticket['assigned_to']) {
        $ticket['assigned_name'] = $ticket['assignee_first_name'] . ' ' . $ticket['assignee_last_name'];
    } else {
        $ticket['assigned_name'] = '';
    }
    
    // Get files
    $ticket['files'] = getTicketFiles($ticketId);
    
    // Get comments
    $ticket['comments'] = getTicketComments($ticketId);
    
    return $ticket;
}

/**
 * Get files attached to a ticket
 * 
 * @param int $ticketId Ticket ID
 * @return array Array of files
 */
function getTicketFiles($ticketId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT f.*
        FROM files f
        JOIN ticket_files tf ON f.file_id = tf.file_id
        WHERE tf.ticket_id = ?
        ORDER BY f.uploaded_at DESC
    ");
    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $files = [];
    
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
    
    return $files;
}

/**
 * Get comments for a ticket
 * 
 * @param int $ticketId Ticket ID
 * @return array Array of comments
 */
function getTicketComments($ticketId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT c.*, u.first_name, u.last_name
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.ticket_id = ?
        ORDER BY c.created_at
    ");
    $stmt->bind_param("i", $ticketId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $comments = [];
    
    while ($row = $result->fetch_assoc()) {
        // Get files attached to this comment
        $row['files'] = getCommentFiles($row['comment_id']);
        $comments[] = $row;
    }
    
    return $comments;
}

/**
 * Get files attached to a comment
 * 
 * @param int $commentId Comment ID
 * @return array Array of files
 */
function getCommentFiles($commentId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT f.*
        FROM files f
        JOIN comment_files cf ON f.file_id = cf.file_id
        WHERE cf.comment_id = ?
        ORDER BY f.uploaded_at DESC
    ");
    $stmt->bind_param("i", $commentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $files = [];
    
    while ($row = $result->fetch_assoc()) {
        $files[] = $row;
    }
    
    return $files;
}

/**
 * Update a ticket
 * 
 * @param int $ticketId Ticket ID
 * @param string $title Ticket title
 * @param string $description Ticket description
 * @param string $url Ticket URL
 * @param string $status Ticket status
 * @param string $priority Ticket priority
 * @param int|null $assignedTo User ID assigned to ticket (optional)
 * @param int $userId User ID making the update
 * @param array $files Array of new uploaded files (optional)
 * @return array Response with status and message
 */
function updateTicket($ticketId, $title, $description, $url, $status, $priorityId, $assignedTo, $userId, $files = []) {
    $conn = getDbConnection();
    
    // Validate input
    if (empty($title)) {
        return ['success' => false, 'message' => 'Ticket title is required.'];
    }
    
    // Get ticket to check permissions
    $ticket = getTicket($ticketId);
    
    if (!$ticket) {
        return ['success' => false, 'message' => 'Ticket not found.'];
    }
    
    $projectId = $ticket['project_id'];
    $oldStatus = $ticket['status'];
    $oldAssignedTo = $ticket['assigned_to'];
    
    // Check if user has permission
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole || $userRole === 'Viewer' || ($userRole === 'Tester' && $ticket['created_by'] !== $userId)) {
        return ['success' => false, 'message' => 'You do not have permission to update this ticket.'];
    }
    
    // If assignedTo is provided, check if user exists and is part of the project
    if ($assignedTo) {
        $assigneeRole = getUserProjectRole($assignedTo, $projectId);
        
        if (!$assigneeRole) {
            return ['success' => false, 'message' => 'Assigned user is not part of this project.'];
        }
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update ticket
        $stmt = $conn->prepare("
            UPDATE tickets 
            SET title = ?, description = ?, url = ?, status_id = ?, priority_id = ?, assigned_to = ?
            WHERE ticket_id = ?
        ");
        $stmt->bind_param(
            "sssiiii", 
            $title, $description, $url, $statusId, $priorityId, $assignedTo, $ticketId
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update ticket: " . $conn->error);
        }
        
        // Process file uploads if any
        if (!empty($files) && is_array($files)) {
            foreach ($files as $file) {
                $fileData = uploadFile($file, $userId);
                
                if ($fileData['success']) {
                    $fileId = $fileData['file_id'];
                    
                    // Link file to ticket
                    $stmt = $conn->prepare("INSERT INTO ticket_files (ticket_id, file_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $ticketId, $fileId);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to link file to ticket: " . $conn->error);
                    }
                }
            }
        }
        
        // Log activity
        if (LOG_ACTIONS) {
            $changes = [
                'title' => $title !== $ticket['title'] ? ['old' => $ticket['title'], 'new' => $title] : null,
                'status_id' => $statusId !== $ticket['status_id'] ? ['old' => $ticket['status_id'], 'new' => $statusId] : null,
                'priority_id' => $priorityId !== $ticket['priority_id'] ? ['old' => $ticket['priority_id'], 'new' => $priorityId] : null,
                'assigned_to' => $assignedTo !== $ticket['assigned_to'] ? ['old' => $ticket['assigned_to'], 'new' => $assignedTo] : null
            ];
            
            // Filter out null values
            $changes = array_filter($changes);
            
            logActivity($userId, $projectId, 'ticket', $ticketId, 'updated', $changes);
        }
        
        // Handle notifications for assignment changes
        if ($assignedTo !== $oldAssignedTo) {
            // If newly assigned to someone
            if ($assignedTo && $assignedTo != $userId) {
                $assigneePrefs = getProjectNotificationPreferences($projectId, $assignedTo);
                
                if ($assigneePrefs && $assigneePrefs['ticket_assigned']) {
                    $assigneeInfo = getUserById($assignedTo);
                    $project = getProject($projectId);
                    
                    if ($assigneeInfo) {
                        $notificationHtml = '
                            <h2>Ticket Assignment</h2>
                            <p>You have been assigned a ticket in the project "' . htmlspecialchars($project['name']) . '".</p>
                            <p><strong>Ticket:</strong> ' . htmlspecialchars($title) . '</p>
                            <p><strong>Priority:</strong> ' . $priority . '</p>
                            <p>Please log in to view and update this ticket.</p>
                        ';
                        
                        $notificationText = "Ticket Assignment\n\n" .
                                       "You have been assigned a ticket in the project \"" . $project['name'] . "\".\n\n" .
                                       "Ticket: " . $title . "\n" .
                                       "Priority: " . $priority . "\n\n" .
                                       "Please log in to view and update this ticket.";
                        
                        sendEmail($assigneeInfo['email'], 'Ticket Assignment: ' . $title, $notificationHtml, $notificationText);
                    }
                }
            }
        }
        
        // Handle notifications for status changes
        if ($status !== $oldStatus) {
            // Notify the creator if they're not the one updating
            if ($ticket['created_by'] != $userId) {
                $creatorPrefs = getProjectNotificationPreferences($projectId, $ticket['created_by']);
                
                if ($creatorPrefs && $creatorPrefs['ticket_status_changed']) {
                    $creatorInfo = getUserById($ticket['created_by']);
                    $project = getProject($projectId);
                    
                    if ($creatorInfo) {
                        $notificationHtml = '
                            <h2>Ticket Status Change</h2>
                            <p>A ticket you created in the project "' . htmlspecialchars($project['name']) . '" has changed status.</p>
                            <p><strong>Ticket:</strong> ' . htmlspecialchars($title) . '</p>
                            <p><strong>Old Status:</strong> ' . $oldStatus . '</p>
                            <p><strong>New Status:</strong> ' . $status . '</p>
                            <p>Please log in to view the ticket.</p>
                        ';
                        
                        $notificationText = "Ticket Status Change\n\n" .
                                       "A ticket you created in the project \"" . $project['name'] . "\" has changed status.\n\n" .
                                       "Ticket: " . $title . "\n" .
                                       "Old Status: " . $oldStatus . "\n" .
                                       "New Status: " . $status . "\n\n" .
                                       "Please log in to view the ticket.";
                        
                        sendEmail($creatorInfo['email'], 'Ticket Status Change: ' . $title, $notificationHtml, $notificationText);
                    }
                }
            }
            
            // Notify the old assignee if they're not the one updating and ticket was assigned
            if ($oldAssignedTo && $oldAssignedTo != $userId) {
                $oldAssigneePrefs = getProjectNotificationPreferences($projectId, $oldAssignedTo);
                
                if ($oldAssigneePrefs && $oldAssigneePrefs['ticket_status_changed']) {
                    $oldAssigneeInfo = getUserById($oldAssignedTo);
                    $project = getProject($projectId);
                    
                    if ($oldAssigneeInfo) {
                        $notificationHtml = '
                            <h2>Ticket Status Change</h2>
                            <p>A ticket assigned to you in the project "' . htmlspecialchars($project['name']) . '" has changed status.</p>
                            <p><strong>Ticket:</strong> ' . htmlspecialchars($title) . '</p>
                            <p><strong>Old Status:</strong> ' . $oldStatus . '</p>
                            <p><strong>New Status:</strong> ' . $status . '</p>
                            <p>Please log in to view the ticket.</p>
                        ';
                        
                        $notificationText = "Ticket Status Change\n\n" .
                                       "A ticket assigned to you in the project \"" . $project['name'] . "\" has changed status.\n\n" .
                                       "Ticket: " . $title . "\n" .
                                       "Old Status: " . $oldStatus . "\n" .
                                       "New Status: " . $status . "\n\n" .
                                       "Please log in to view the ticket.";
                        
                        sendEmail($oldAssigneeInfo['email'], 'Ticket Status Change: ' . $title, $notificationHtml, $notificationText);
                    }
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        return ['success' => true, 'message' => 'Ticket updated successfully.'];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Delete a ticket
 * 
 * @param int $ticketId Ticket ID
 * @param int $userId User ID making the deletion
 * @return array Response with status and message
 */
function deleteTicket($ticketId, $userId) {
    $conn = getDbConnection();
    
    // Get ticket to check permissions
    $ticket = getTicket($ticketId);
    
    if (!$ticket) {
        return ['success' => false, 'message' => 'Ticket not found.'];
    }
    
    $projectId = $ticket['project_id'];
    
    // Check if user has permission
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole || $userRole === 'Viewer' || $userRole === 'Tester' || 
        ($userRole !== 'Owner' && $userRole !== 'Project Manager' && $ticket['created_by'] !== $userId)) {
        return ['success' => false, 'message' => 'You do not have permission to delete this ticket.'];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
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
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete ticket: " . $conn->error);
        }
        
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'ticket', $ticketId, 'deleted', [
                'title' => $ticket['title']
            ]);
        }
        
        // Commit transaction
        $conn->commit();
        
        return ['success' => true, 'message' => 'Ticket deleted successfully.'];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Assign a ticket to a user
 * 
 * @param int $ticketId Ticket ID
 * @param int|null $assignedTo User ID to assign ticket to (null to unassign)
 * @param int $userId User ID making the assignment
 * @return array Response with status and message
 */
function assignTicket($ticketId, $assignedTo, $userId) {
    $conn = getDbConnection();
    
    // Get ticket to check permissions
    $ticket = getTicket($ticketId);
    
    if (!$ticket) {
        return ['success' => false, 'message' => 'Ticket not found.'];
    }
    
    $projectId = $ticket['project_id'];
    $oldAssignedTo = $ticket['assigned_to'];
    
    // Check if user has permission
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole || $userRole === 'Viewer' || $userRole === 'Tester') {
        return ['success' => false, 'message' => 'You do not have permission to assign tickets.'];
    }
    
    // If assignedTo is provided, check if user exists and is part of the project
    if ($assignedTo) {
        $assigneeRole = getUserProjectRole($assignedTo, $projectId);
        
        if (!$assigneeRole) {
            return ['success' => false, 'message' => 'Assigned user is not part of this project.'];
        }
    }
    
    // Update ticket
    $stmt = $conn->prepare("UPDATE tickets SET assigned_to = ? WHERE ticket_id = ?");
    $stmt->bind_param("ii", $assignedTo, $ticketId);
    
    if ($stmt->execute()) {
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'ticket', $ticketId, 'assigned', [
                'old_assigned_to' => $oldAssignedTo,
                'new_assigned_to' => $assignedTo
            ]);
        }
        
        // Send notification to newly assigned user
        if ($assignedTo && $assignedTo != $userId) {
            $assigneePrefs = getProjectNotificationPreferences($projectId, $assignedTo);
            
            if ($assigneePrefs && $assigneePrefs['ticket_assigned']) {
                $assigneeInfo = getUserById($assignedTo);
                $project = getProject($projectId);
                
                if ($assigneeInfo) {
                    $notificationHtml = '
                        <h2>Ticket Assignment</h2>
                        <p>You have been assigned a ticket in the project "' . htmlspecialchars($project['name']) . '".</p>
                        <p><strong>Ticket:</strong> ' . htmlspecialchars($ticket['title']) . '</p>
                        <p><strong>Priority:</strong> ' . $ticket['priority'] . '</p>
                        <p>Please log in to view and update this ticket.</p>
                    ';
                    
                    $notificationText = "Ticket Assignment\n\n" .
                                   "You have been assigned a ticket in the project \"" . $project['name'] . "\".\n\n" .
                                   "Ticket: " . $ticket['title'] . "\n" .
                                   "Priority: " . $ticket['priority'] . "\n\n" .
                                   "Please log in to view and update this ticket.";
                    
                    sendEmail($assigneeInfo['email'], 'Ticket Assignment: ' . $ticket['title'], $notificationHtml, $notificationText);
                }
            }
        }
        
        return ['success' => true, 'message' => 'Ticket assigned successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to assign ticket: ' . $conn->error];
    }
}

/**
 * Change ticket status
 * 
 * @param int $ticketId Ticket ID
 * @param string $status New status
 * @param int $userId User ID making the change
 * @return array Response with status and message
 */
function changeTicketStatus($ticketId, $statusId, $userId) {
    $conn = getDbConnection();
    
    // Validate status ID
    if ($statusId < 1 || $statusId > 8) {
        return ['success' => false, 'message' => 'Invalid status.'];
    }
    
    // Get ticket to check permissions
    $ticket = getTicket($ticketId);
    
    if (!$ticket) {
        return ['success' => false, 'message' => 'Ticket not found.'];
    }
    
    $projectId = $ticket['project_id'];
    $oldStatus = $ticket['status'];
    
    // Check if user has permission
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole || $userRole === 'Viewer' || $userRole === 'Tester') {
        return ['success' => false, 'message' => 'You do not have permission to change ticket status.'];
    }
    
    // Update ticket
    $stmt = $conn->prepare("UPDATE tickets SET status_id = ? WHERE ticket_id = ?");
    $stmt->bind_param("ii", $statusId, $ticketId);
    
    if ($stmt->execute()) {
        // Log activity
        if (LOG_ACTIONS) {
           logActivity($userId, $projectId, 'ticket', $ticketId, 'status_changed', [
               'old_status_id' => $oldStatus,
               'new_status_id' => $statusId
            ]);
        }
        
        // Notify relevant users about status change
        
        // Notify the creator if they're not the one updating
        if ($ticket['created_by'] != $userId) {
            $creatorPrefs = getProjectNotificationPreferences($projectId, $ticket['created_by']);
            
            if ($creatorPrefs && $creatorPrefs['ticket_status_changed']) {
                $creatorInfo = getUserById($ticket['created_by']);
                $project = getProject($projectId);
                
                if ($creatorInfo) {
                    $notificationHtml = '
                        <h2>Ticket Status Change</h2>
                        <p>A ticket you created in the project "' . htmlspecialchars($project['name']) . '" has changed status.</p>
                        <p><strong>Ticket:</strong> ' . htmlspecialchars($ticket['title']) . '</p>
                        <p><strong>Old Status:</strong> ' . $oldStatus . '</p>
                        <p><strong>New Status:</strong> ' . $status . '</p>
                        <p>Please log in to view the ticket.</p>
                    ';
                    
                    $notificationText = "Ticket Status Change\n\n" .
                                   "A ticket you created in the project \"" . $project['name'] . "\" has changed status.\n\n" .
                                   "Ticket: " . $ticket['title'] . "\n" .
                                   "Old Status: " . $oldStatus . "\n" .
                                   "New Status: " . $status . "\n\n" .
                                   "Please log in to view the ticket.";
                    
                    sendEmail($creatorInfo['email'], 'Ticket Status Change: ' . $ticket['title'], $notificationHtml, $notificationText);
                }
            }
        }
        
        // Notify the assignee if they're not the one updating and ticket is assigned
        if ($ticket['assigned_to'] && $ticket['assigned_to'] != $userId) {
            $assigneePrefs = getProjectNotificationPreferences($projectId, $ticket['assigned_to']);
            
            if ($assigneePrefs && $assigneePrefs['ticket_status_changed']) {
                $assigneeInfo = getUserById($ticket['assigned_to']);
                $project = getProject($projectId);
                
                if ($assigneeInfo) {
                    $notificationHtml = '
                        <h2>Ticket Status Change</h2>
                        <p>A ticket assigned to you in the project "' . htmlspecialchars($project['name']) . '" has changed status.</p>
                        <p><strong>Ticket:</strong> ' . htmlspecialchars($ticket['title']) . '</p>
                        <p><strong>Old Status:</strong> ' . $oldStatus . '</p>
                        <p><strong>New Status:</strong> ' . $status . '</p>
                        <p>Please log in to view the ticket.</p>
                    ';
                    
                    $notificationText = "Ticket Status Change\n\n" .
                                   "A ticket assigned to you in the project \"" . $project['name'] . "\" has changed status.\n\n" .
                                   "Ticket: " . $ticket['title'] . "\n" .
                                   "Old Status: " . $oldStatus . "\n" .
                                   "New Status: " . $status . "\n\n" .
                                   "Please log in to view the ticket.";
                    
                    sendEmail($assigneeInfo['email'], 'Ticket Status Change: ' . $ticket['title'], $notificationHtml, $notificationText);
                }
            }
        }
        
        return ['success' => true, 'message' => 'Ticket status changed successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to change ticket status: ' . $conn->error];
    }
}

/**
 * Reorder tickets within a deliverable
 * 
 * @param int $deliverableId Deliverable ID
 * @param array $order Array of ticket IDs in the new order
 * @param int $userId User ID making the reorder
 * @return array Response with status and message
 */
function reorderTickets($deliverableId, $order, $userId) {
    $conn = getDbConnection();
    
    if (empty($order)) {
        return ['success' => false, 'message' => 'Order is required.'];
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
        return ['success' => false, 'message' => 'You do not have permission to reorder tickets.'];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        foreach ($order as $index => $ticketId) {
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
                'order' => $order
            ]);
        }
        
        // Commit transaction
        $conn->commit();
        
        return ['success' => true, 'message' => 'Tickets reordered successfully.'];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}