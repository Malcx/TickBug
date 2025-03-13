<?php
// includes/comments.php
// Comment related functions

/**
 * Add a comment to a ticket
 * 
 * @param int $ticketId Ticket ID
 * @param string $description Comment text
 * @param string $url Optional URL
 * @param int $userId User ID making the comment
 * @param array $files Array of uploaded files (optional)
 * @return array Response with status and comment data
 */
function addComment($ticketId, $description, $url, $userId, $files = []) {
    $conn = getDbConnection();
    
    // Validate input
    if (empty($description) && empty($files)) {
        return ['success' => false, 'message' => 'Comment text or files are required.'];
    }
    
    // Get ticket to check project ID
    $ticket = getTicket($ticketId);
    
    if (!$ticket) {
        return ['success' => false, 'message' => 'Ticket not found.'];
    }
    
    $projectId = $ticket['project_id'];
    
    // Check if user has permission
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole || $userRole === 'Viewer') {
        return ['success' => false, 'message' => 'You do not have permission to comment on tickets.'];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert comment
        $stmt = $conn->prepare("
            INSERT INTO comments (ticket_id, user_id, description, url)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("iiss", $ticketId, $userId, $description, $url);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to add comment: " . $conn->error);
        }
        
        $commentId = $conn->insert_id;
        
        // Process file uploads if any
        $uploadedFileIds = [];
        
        if (!empty($files) && is_array($files)) {
            foreach ($files as $file) {
                $fileData = uploadFile($file, $userId);
                
                if ($fileData['success']) {
                    $fileId = $fileData['file_id'];
                    $uploadedFileIds[] = $fileId;
                    
                    // Link file to comment
                    $stmt = $conn->prepare("INSERT INTO comment_files (comment_id, file_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $commentId, $fileId);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to link file to comment: " . $conn->error);
                    }
                }
            }
        }
        
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'comment', $commentId, 'created', [
                'ticket_id' => $ticketId
            ]);
        }
        
        // Send notifications to relevant users
        
        // Notify ticket creator if different from commenter
        if ($ticket['created_by'] != $userId) {
            $creatorPrefs = getProjectNotificationPreferences($projectId, $ticket['created_by']);
            
            if ($creatorPrefs && $creatorPrefs['ticket_commented']) {
                $creatorInfo = getUserById($ticket['created_by']);
                $project = getProject($projectId);
                $userInfo = getUserById($userId);
                
                if ($creatorInfo && $userInfo) {
                    $commenterName = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
                    
                    $notificationHtml = '
                        <h2>New Comment on Your Ticket</h2>
                        <p>' . htmlspecialchars($commenterName) . ' has commented on your ticket in the project "' . htmlspecialchars($project['name']) . '".</p>
                        <p><strong>Ticket:</strong> ' . htmlspecialchars($ticket['title']) . '</p>
                        <p><strong>Comment:</strong> ' . htmlspecialchars(substr($description, 0, 100)) . (strlen($description) > 100 ? '...' : '') . '</p>
                        <p>Please log in to view the full comment and reply.</p>
                    ';
                    
                    $notificationText = "New Comment on Your Ticket\n\n" .
                                   $commenterName . " has commented on your ticket in the project \"" . $project['name'] . "\".\n\n" .
                                   "Ticket: " . $ticket['title'] . "\n" .
                                   "Comment: " . substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '') . "\n\n" .
                                   "Please log in to view the full comment and reply.";
                    
                    sendEmail($creatorInfo['email'], 'New Comment: ' . $ticket['title'], $notificationHtml, $notificationText);
                }
            }
        }
        
        // Notify assignee if different from commenter and creator
        if ($ticket['assigned_to'] && $ticket['assigned_to'] != $userId && $ticket['assigned_to'] != $ticket['created_by']) {
            $assigneePrefs = getProjectNotificationPreferences($projectId, $ticket['assigned_to']);
            
            if ($assigneePrefs && $assigneePrefs['ticket_commented']) {
                $assigneeInfo = getUserById($ticket['assigned_to']);
                $project = getProject($projectId);
                $userInfo = getUserById($userId);
                
                if ($assigneeInfo && $userInfo) {
                    $commenterName = $userInfo['first_name'] . ' ' . $userInfo['last_name'];
                    
                    $notificationHtml = '
                        <h2>New Comment on Assigned Ticket</h2>
                        <p>' . htmlspecialchars($commenterName) . ' has commented on a ticket assigned to you in the project "' . htmlspecialchars($project['name']) . '".</p>
                        <p><strong>Ticket:</strong> ' . htmlspecialchars($ticket['title']) . '</p>
                        <p><strong>Comment:</strong> ' . htmlspecialchars(substr($description, 0, 100)) . (strlen($description) > 100 ? '...' : '') . '</p>
                        <p>Please log in to view the full comment and reply.</p>
                    ';
                    
                    $notificationText = "New Comment on Assigned Ticket\n\n" .
                                   $commenterName . " has commented on a ticket assigned to you in the project \"" . $project['name'] . "\".\n\n" .
                                   "Ticket: " . $ticket['title'] . "\n" .
                                   "Comment: " . substr($description, 0, 100) . (strlen($description) > 100 ? '...' : '') . "\n\n" .
                                   "Please log in to view the full comment and reply.";
                    
                    sendEmail($assigneeInfo['email'], 'New Comment: ' . $ticket['title'], $notificationHtml, $notificationText);
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Get user info for response
        $userInfo = getUserById($userId);
        
        return [
            'success' => true, 
            'message' => 'Comment added successfully.',
            'comment' => [
                'comment_id' => $commentId,
                'ticket_id' => $ticketId,
                'user_id' => $userId,
                'first_name' => $userInfo['first_name'],
                'last_name' => $userInfo['last_name'],
                'description' => $description,
                'url' => $url,
                'created_at' => date('Y-m-d H:i:s'),
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
 * Update a comment
 * 
 * @param int $commentId Comment ID
 * @param string $description Comment text
 * @param string $url Optional URL
 * @param int $userId User ID making the update
 * @param array $files Array of new uploaded files (optional)
 * @return array Response with status and message
 */
function updateComment($commentId, $description, $url, $userId, $files = []) {
    $conn = getDbConnection();
    
    // Validate input
    if (empty($description) && empty($files)) {
        return ['success' => false, 'message' => 'Comment text or files are required.'];
    }
    
    // Get comment to check permissions
    $stmt = $conn->prepare("
        SELECT c.*, t.deliverable_id, d.project_id
        FROM comments c
        JOIN tickets t ON c.ticket_id = t.ticket_id
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        WHERE c.comment_id = ?
    ");
    $stmt->bind_param("i", $commentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Comment not found.'];
    }
    
    $comment = $result->fetch_assoc();
    $ticketId = $comment['ticket_id'];
    $projectId = $comment['project_id'];
    
    // Only the comment author or project admin can update the comment
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (($comment['user_id'] !== $userId) && 
        ($userRole !== 'Owner' && $userRole !== 'Project Manager')) {
        return ['success' => false, 'message' => 'You do not have permission to update this comment.'];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update comment
        $stmt = $conn->prepare("
            UPDATE comments
            SET description = ?, url = ?
            WHERE comment_id = ?
        ");
        $stmt->bind_param("ssi", $description, $url, $commentId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update comment: " . $conn->error);
        }
        
        // Process file uploads if any
        if (!empty($files) && is_array($files)) {
            foreach ($files as $file) {
                $fileData = uploadFile($file, $userId);
                
                if ($fileData['success']) {
                    $fileId = $fileData['file_id'];
                    
                    // Link file to comment
                    $stmt = $conn->prepare("INSERT INTO comment_files (comment_id, file_id) VALUES (?, ?)");
                    $stmt->bind_param("ii", $commentId, $fileId);
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Failed to link file to comment: " . $conn->error);
                    }
                }
            }
        }
        
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'comment', $commentId, 'updated', [
                'ticket_id' => $ticketId
            ]);
        }
        
        // Commit transaction
        $conn->commit();
        
        return ['success' => true, 'message' => 'Comment updated successfully.'];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Delete a comment
 * 
 * @param int $commentId Comment ID
 * @param int $userId User ID making the deletion
 * @return array Response with status and message
 */
function deleteComment($commentId, $userId) {
    $conn = getDbConnection();
    
    // Get comment to check permissions
    $stmt = $conn->prepare("
        SELECT c.*, t.deliverable_id, d.project_id
        FROM comments c
        JOIN tickets t ON c.ticket_id = t.ticket_id
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        WHERE c.comment_id = ?
    ");
    $stmt->bind_param("i", $commentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Comment not found.'];
    }
    
    $comment = $result->fetch_assoc();
    $ticketId = $comment['ticket_id'];
    $projectId = $comment['project_id'];
    
    // Only the comment author or project admin can delete the comment
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (($comment['user_id'] !== $userId) && 
        ($userRole !== 'Owner' && $userRole !== 'Project Manager')) {
        return ['success' => false, 'message' => 'You do not have permission to delete this comment.'];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete comment files
        $stmt = $conn->prepare("
            DELETE f FROM files f 
            JOIN comment_files cf ON f.file_id = cf.file_id 
            WHERE cf.comment_id = ?
        ");
        $stmt->bind_param("i", $commentId);
        $stmt->execute();
        
        // Delete comment
        $stmt = $conn->prepare("DELETE FROM comments WHERE comment_id = ?");
        $stmt->bind_param("i", $commentId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete comment: " . $conn->error);
        }
        
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'comment', $commentId, 'deleted', [
                'ticket_id' => $ticketId
            ]);
        }
        
        // Commit transaction
        $conn->commit();
        
        return ['success' => true, 'message' => 'Comment deleted successfully.'];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}