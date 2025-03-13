<?php
// includes/activity.php
// Activity log related functions

/**
 * Log an activity
 * 
 * @param int $userId User ID performing the action
 * @param int $projectId Project ID
 * @param string $targetType Type of target (project, deliverable, ticket, comment, file, user)
 * @param int $targetId ID of the target
 * @param string $action Action performed
 * @param array $details Additional details about the action
 * @return bool True if logged successfully, false otherwise
 */
function logActivity($userId, $projectId, $targetType, $targetId, $action, $details = []) {
    $conn = getDbConnection();
    
    // Convert details to JSON
    $detailsJson = json_encode($details);
    
    // Insert log entry
    $stmt = $conn->prepare("
        INSERT INTO activity_log (user_id, project_id, target_type, target_id, action, details)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iisiss", $userId, $projectId, $targetType, $targetId, $action, $detailsJson);
    
    return $stmt->execute();
}

/**
 * Get activity log for a project
 * 
 * @param int $projectId Project ID
 * @param int $limit Number of entries to return (optional)
 * @param int $offset Offset for pagination (optional)
 * @return array Array of activity log entries
 */
function getProjectActivityLog($projectId, $limit = 50, $offset = 0) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT a.*, u.first_name, u.last_name
        FROM activity_log a
        JOIN users u ON a.user_id = u.user_id
        WHERE a.project_id = ?
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $projectId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    
    while ($row = $result->fetch_assoc()) {
        // Convert JSON details to array
        $row['details'] = json_decode($row['details'], true);
        
        // Add user name
        $row['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
        
        // Add formatted timestamp
        $row['formatted_time'] = date('M j, Y g:i A', strtotime($row['created_at']));
        
        // Add formatted action description
        $row['description'] = formatActivityDescription($row);
        
        $activities[] = $row;
    }
    
    return $activities;
}

/**
 * Get activity log for a user
 * 
 * @param int $userId User ID
 * @param int $limit Number of entries to return (optional)
 * @param int $offset Offset for pagination (optional)
 * @return array Array of activity log entries
 */
function getUserActivityLog($userId, $limit = 50, $offset = 0) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT a.*, u.first_name, u.last_name, p.name as project_name
        FROM activity_log a
        JOIN users u ON a.user_id = u.user_id
        JOIN projects p ON a.project_id = p.project_id
        WHERE a.user_id = ?
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param("iii", $userId, $limit, $offset);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $activities = [];
    
    while ($row = $result->fetch_assoc()) {
        // Convert JSON details to array
        $row['details'] = json_decode($row['details'], true);
        
        // Add user name
        $row['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
        
        // Add formatted timestamp
        $row['formatted_time'] = date('M j, Y g:i A', strtotime($row['created_at']));
        
        // Add formatted action description
        $row['description'] = formatActivityDescription($row);
        
        $activities[] = $row;
    }
    
    return $activities;
}

/**
 * Format activity description for display
 * 
 * @param array $activity Activity log entry
 * @return string Formatted description
 */
function formatActivityDescription($activity) {
    $userName = $activity['first_name'] . ' ' . $activity['last_name'];
    $targetType = $activity['target_type'];
    $action = $activity['action'];
    $details = $activity['details'];
    
    switch ($targetType) {
        case 'project':
            switch ($action) {
                case 'created':
                    return $userName . ' created the project "' . htmlspecialchars($details['name']) . '"';
                case 'updated':
                    return $userName . ' updated the project';
                case 'archived':
                    return $userName . ' archived the project';
                case 'unarchived':
                    return $userName . ' unarchived the project';
                default:
                    return $userName . ' performed action "' . $action . '" on the project';
            }
            
        case 'deliverable':
            switch ($action) {
                case 'created':
                    return $userName . ' created a new deliverable "' . htmlspecialchars($details['name']) . '"';
                case 'updated':
                    return $userName . ' updated the deliverable';
                case 'deleted':
                    return $userName . ' deleted the deliverable "' . htmlspecialchars($details['name']) . '"';
                case 'reordered':
                    return $userName . ' reordered the deliverables';
                default:
                    return $userName . ' performed action "' . $action . '" on a deliverable';
            }
            
        case 'ticket':
            switch ($action) {
                case 'created':
                    return $userName . ' created a new ticket "' . htmlspecialchars($details['title']) . '"';
                case 'updated':
                    $changes = [];
                    if (isset($details['status'])) {
                        $changes[] = 'status from "' . $details['status']['old'] . '" to "' . $details['status']['new'] . '"';
                    }
                    if (isset($details['priority'])) {
                        $changes[] = 'priority from "' . $details['priority']['old'] . '" to "' . $details['priority']['new'] . '"';
                    }
                    if (isset($details['assigned_to'])) {
                        $oldAssignee = $details['assigned_to']['old'] ? getUserById($details['assigned_to']['old']) : null;
                        $newAssignee = $details['assigned_to']['new'] ? getUserById($details['assigned_to']['new']) : null;
                        
                        $oldName = $oldAssignee ? $oldAssignee['first_name'] . ' ' . $oldAssignee['last_name'] : 'Unassigned';
                        $newName = $newAssignee ? $newAssignee['first_name'] . ' ' . $newAssignee['last_name'] : 'Unassigned';
                        
                        $changes[] = 'assignment from "' . $oldName . '" to "' . $newName . '"';
                    }
                    
                    if (empty($changes)) {
                        return $userName . ' updated a ticket';
                    } else {
                        return $userName . ' changed ticket ' . implode(', ', $changes);
                    }
                case 'deleted':
                    return $userName . ' deleted the ticket "' . htmlspecialchars($details['title']) . '"';
                case 'assigned':
                    $oldAssignee = $details['old_assigned_to'] ? getUserById($details['old_assigned_to']) : null;
                    $newAssignee = $details['new_assigned_to'] ? getUserById($details['new_assigned_to']) : null;
                    
                    $oldName = $oldAssignee ? $oldAssignee['first_name'] . ' ' . $oldAssignee['last_name'] : 'Unassigned';
                    $newName = $newAssignee ? $newAssignee['first_name'] . ' ' . $newAssignee['last_name'] : 'Unassigned';
                    
                    return $userName . ' reassigned ticket from "' . $oldName . '" to "' . $newName . '"';
                case 'status_changed':
                    return $userName . ' changed ticket status from "' . $details['old_status'] . '" to "' . $details['new_status'] . '"';
                case 'reordered':
                    return $userName . ' reordered the tickets';
                default:
                    return $userName . ' performed action "' . $action . '" on a ticket';
            }
            
        case 'comment':
            switch ($action) {
                case 'created':
                    return $userName . ' added a comment to a ticket';
                case 'updated':
                    return $userName . ' updated a comment';
                case 'deleted':
                    return $userName . ' deleted a comment';
                default:
                    return $userName . ' performed action "' . $action . '" on a comment';
            }
            
        case 'file':
            switch ($action) {
                case 'deleted':
                    return $userName . ' deleted the file "' . htmlspecialchars($details['filename']) . '"';
                default:
                    return $userName . ' performed action "' . $action . '" on a file';
            }
            
        case 'user':
            switch ($action) {
                case 'added':
                    $targetUser = getUserById($activity['target_id']);
                    $targetName = $targetUser ? $targetUser['first_name'] . ' ' . $targetUser['last_name'] : 'User #' . $activity['target_id'];
                    return $userName . ' added ' . $targetName . ' to the project as ' . $details['role'];
                case 'removed':
                    $targetUser = getUserById($activity['target_id']);
                    $targetName = $targetUser ? $targetUser['first_name'] . ' ' . $targetUser['last_name'] : 'User #' . $activity['target_id'];
                    return $userName . ' removed ' . $targetName . ' from the project';
                case 'role_changed':
                    $targetUser = getUserById($activity['target_id']);
                    $targetName = $targetUser ? $targetUser['first_name'] . ' ' . $targetUser['last_name'] : 'User #' . $activity['target_id'];
                    return $userName . ' changed ' . $targetName . '\'s role from "' . $details['old_role'] . '" to "' . $details['new_role'] . '"';
                default:
                    return $userName . ' performed action "' . $action . '" on a user';
            }
            
        default:
            return $userName . ' performed action "' . $action . '" on ' . $targetType . ' #' . $activity['target_id'];
    }
}

/**
 * Get activity count for a project
 * 
 * @param int $projectId Project ID
 * @return int Number of activity log entries
 */
function getProjectActivityCount($projectId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count
        FROM activity_log
        WHERE project_id = ?
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    return $row['count'];
}

/**
 * Send notifications to project members
 * 
 * @param int $projectId Project ID
 * @param int $senderId User ID sending the notification
 * @param string $type Notification type
 * @param array $data Additional data for the notification
 * @return bool Success status
 */
function notifyProjectMembers($projectId, $senderId, $type, $data = []) {
    $conn = getDbConnection();
    
    // Get project users
    $stmt = $conn->prepare("
        SELECT pu.user_id, pu.notification_preferences, u.email, u.first_name, u.last_name
        FROM project_users pu
        JOIN users u ON pu.user_id = u.user_id
        WHERE pu.project_id = ? AND pu.user_id != ?
    ");
    $stmt->bind_param("ii", $projectId, $senderId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notified = false;
    
    while ($user = $result->fetch_assoc()) {
        $preferences = json_decode($user['notification_preferences'], true);
        
        // If no preferences set, use defaults
        if (!$preferences) {
            $preferences = [
                'ticket_assigned' => true,
                'ticket_status_changed' => true,
                'ticket_commented' => true,
                'deliverable_created' => true,
                'project_user_added' => true
            ];
        }
        
        // Check if user wants this type of notification
        if (isset($preferences[$type]) && $preferences[$type]) {
            $project = getProject($projectId);
            $sender = getUserById($senderId);
            
            switch ($type) {
                case 'deliverable_created':
                    $subject = 'New Deliverable in Project: ' . $project['name'];
                    $html = '
                        <h2>New Deliverable Added</h2>
                        <p>' . htmlspecialchars($sender['first_name'] . ' ' . $sender['last_name']) . ' has added a new deliverable to the project "' . htmlspecialchars($project['name']) . '".</p>
                        <p><strong>Deliverable:</strong> ' . htmlspecialchars($data['deliverable_name']) . '</p>
                        <p>Please log in to view the details.</p>
                    ';
                    
                    $text = "New Deliverable Added\n\n" .
                           $sender['first_name'] . ' ' . $sender['last_name'] . " has added a new deliverable to the project \"" . $project['name'] . "\".\n\n" .
                           "Deliverable: " . $data['deliverable_name'] . "\n\n" .
                           "Please log in to view the details.";
                    
                    sendEmail($user['email'], $subject, $html, $text);
                    $notified = true;
                    break;
                
                // Additional notification types can be added here
            }
        }
    }
    
    return $notified;
}