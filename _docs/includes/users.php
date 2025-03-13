<?php
// includes/users.php
// User related functions

/**
 * Get a user by ID
 * 
 * @param int $userId User ID
 * @return array|false User data or false if not found
 */
function getUserById($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT user_id, email, first_name, last_name FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

/**
 * Get a user by email
 * 
 * @param string $email User email
 * @return array|false User data or false if not found
 */
function getUserByEmail($email) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT user_id, email, first_name, last_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

/**
 * Get all users for a project
 * 
 * @param int $projectId Project ID
 * @return array Array of users with their roles
 */
function getProjectUsers($projectId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT u.user_id, u.email, u.first_name, u.last_name, pu.role
        FROM users u
        JOIN project_users pu ON u.user_id = pu.user_id
        WHERE pu.project_id = ?
        ORDER BY 
            CASE 
                WHEN pu.role = 'Owner' THEN 1
                WHEN pu.role = 'Project Manager' THEN 2
                WHEN pu.role = 'Developer' THEN 3
                WHEN pu.role = 'Designer' THEN 4
                WHEN pu.role = 'Reviewer' THEN 5
                WHEN pu.role = 'Tester' THEN 6
                WHEN pu.role = 'Viewer' THEN 7
                ELSE 8
            END,
            u.first_name, u.last_name
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

/**
 * Search for users by name or email
 * 
 * @param string $searchTerm Search term
 * @param int $limit Maximum number of results
 * @return array Array of matching users
 */
function searchUsers($searchTerm, $limit = 10) {
    $conn = getDbConnection();
    
    $searchTerm = '%' . $searchTerm . '%';
    
    $stmt = $conn->prepare("
        SELECT user_id, email, first_name, last_name
        FROM users
        WHERE email LIKE ? OR first_name LIKE ? OR last_name LIKE ?
        LIMIT ?
    ");
    $stmt->bind_param("sssi", $searchTerm, $searchTerm, $searchTerm, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    return $users;
}

/**
 * Get statistics for a user
 * 
 * @param int $userId User ID
 * @return array User statistics
 */
function getUserStatistics($userId) {
    $conn = getDbConnection();
    
    // Count projects
    $stmt = $conn->prepare("
        SELECT COUNT(*) as project_count
        FROM project_users
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $projectCount = $row['project_count'];
    
    // Count tickets created
    $stmt = $conn->prepare("
        SELECT COUNT(*) as created_count
        FROM tickets
        WHERE created_by = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $createdCount = $row['created_count'];
    
    // Count tickets assigned
    $stmt = $conn->prepare("
        SELECT COUNT(*) as assigned_count
        FROM tickets
        WHERE assigned_to = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $assignedCount = $row['assigned_count'];
    
    // Count tickets completed (assigned and status is Complete)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as completed_count
        FROM tickets
        WHERE assigned_to = ? AND status = 'Complete'
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $completedCount = $row['completed_count'];
    
    // Count comments
    $stmt = $conn->prepare("
        SELECT COUNT(*) as comment_count
        FROM comments
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $commentCount = $row['comment_count'];
    
    return [
        'projects' => $projectCount,
        'tickets_created' => $createdCount,
        'tickets_assigned' => $assignedCount,
        'tickets_completed' => $completedCount,
        'comments' => $commentCount
    ];
}

/**
 * Get notifications settings for all projects for a user
 * 
 * @param int $userId User ID
 * @return array Array of project notification settings
 */
function getUserNotificationSettings($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT pu.project_id, p.name as project_name, pu.notification_preferences
        FROM project_users pu
        JOIN projects p ON pu.project_id = p.project_id
        WHERE pu.user_id = ?
        ORDER BY p.name
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $settings = [];
    
    while ($row = $result->fetch_assoc()) {
        $projectId = $row['project_id'];
        $projectName = $row['project_name'];
        
        if ($row['notification_preferences'] === null) {
            // Default preferences
            $preferences = [
                'ticket_assigned' => true,
                'ticket_status_changed' => true,
                'ticket_commented' => true,
                'deliverable_created' => true,
                'project_user_added' => true
            ];
        } else {
            $preferences = json_decode($row['notification_preferences'], true);
        }
        
        $settings[] = [
            'project_id' => $projectId,
            'project_name' => $projectName,
            'preferences' => $preferences
        ];
    }
    
    return $settings;
}

/**
 * Get recent activity for user dashboard
 * 
 * @param int $userId User ID
 * @param int $limit Number of items to return
 * @return array Array of recent activity items
 */
function getUserDashboardActivity($userId, $limit = 10) {
    $conn = getDbConnection();
    
    // Get projects the user is part of
    $stmt = $conn->prepare("
        SELECT project_id
        FROM project_users
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $projectIds = [];
    while ($row = $result->fetch_assoc()) {
        $projectIds[] = $row['project_id'];
    }
    
    if (empty($projectIds)) {
        return [];
    }
    
    // Get recent activity from these projects
    $placeholders = implode(',', array_fill(0, count($projectIds), '?'));
    $sql = "
        SELECT a.*, u.first_name, u.last_name, p.name as project_name
        FROM activity_log a
        JOIN users u ON a.user_id = u.user_id
        JOIN projects p ON a.project_id = p.project_id
        WHERE a.project_id IN ($placeholders)
        ORDER BY a.created_at DESC
        LIMIT ?
    ";
    
    $stmt = $conn->prepare($sql);
    
    // Bind parameters
    $types = str_repeat('i', count($projectIds)) . 'i';
    $params = array_merge($projectIds, [$limit]);
    
    // Dynamic binding
    $bindParams = array_merge([$types], $params);
    $stmt->bind_param(...$bindParams);
    
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
 * Get assigned tickets for a user
 * 
 * @param int $userId User ID
 * @param bool $includeCompleted Whether to include completed tickets
 * @return array Array of assigned tickets
 */
function getUserAssignedTickets($userId, $includeCompleted = false) {
    $conn = getDbConnection();
    
    $statusCondition = $includeCompleted ? '' : " AND t.status != 'Complete' AND t.status != 'Rejected' AND t.status != 'Ignored'";
    
    $stmt = $conn->prepare("
        SELECT t.*, 
               d.name as deliverable_name, 
               p.project_id, p.name as project_name,
               creator.first_name as creator_first_name, 
               creator.last_name as creator_last_name
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        JOIN projects p ON d.project_id = p.project_id
        JOIN users creator ON t.created_by = creator.user_id
        WHERE t.assigned_to = ?" . $statusCondition . "
        ORDER BY 
            CASE 
                WHEN t.priority = '1-Critical' THEN 1
                WHEN t.priority = '1-Important' THEN 2
                WHEN t.priority = '2-Nice to have' THEN 3
                WHEN t.priority = '3-Feature Request' THEN 4
                WHEN t.priority = '4-Nice to have' THEN 5
                ELSE 6
            END,
            t.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tickets = [];
    
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
    
    return $tickets;
}

/**
 * Get tickets created by a user
 * 
 * @param int $userId User ID
 * @param bool $includeCompleted Whether to include completed tickets
 * @return array Array of created tickets
 */
function getUserCreatedTickets($userId, $includeCompleted = false) {
    $conn = getDbConnection();
    
    $statusCondition = $includeCompleted ? '' : " AND t.status != 'Complete' AND t.status != 'Rejected' AND t.status != 'Ignored'";
    
    $stmt = $conn->prepare("
        SELECT t.*, 
               d.name as deliverable_name, 
               p.project_id, p.name as project_name,
               assignee.first_name as assignee_first_name, 
               assignee.last_name as assignee_last_name
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        JOIN projects p ON d.project_id = p.project_id
        LEFT JOIN users assignee ON t.assigned_to = assignee.user_id
        WHERE t.created_by = ?" . $statusCondition . "
        ORDER BY t.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $tickets = [];
    
    while ($row = $result->fetch_assoc()) {
        if ($row['assigned_to']) {
            $row['assigned_name'] = $row['assignee_first_name'] . ' ' . $row['assignee_last_name'];
        } else {
            $row['assigned_name'] = '';
        }
        
        $tickets[] = $row;
    }
    
    return $tickets;
}