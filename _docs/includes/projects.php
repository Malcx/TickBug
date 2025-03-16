<?php
// includes/projects.php
// Project related functions

/**
 * Create a new project
 * 
 * @param string $name Project name
 * @param string $description Project description
 * @param string $themeColor Project theme color
 * @param int $userId User ID of creator
 * @return array Response with status and project data
 */
function createProject($name, $description, $themeColor, $userId) {
    $conn = getDbConnection();
    
    // Validate input
    if (empty($name)) {
        return ['success' => false, 'message' => 'Project name is required.'];
    }
    
    // Validate color format (should be a hex color like #201E5B)
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $themeColor)) {
        $themeColor = '#201E5B'; // Use default if invalid
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert project
        $stmt = $conn->prepare("INSERT INTO projects (name, description, theme_color, created_by) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("sssi", $name, $description, $themeColor, $userId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to create project: " . $conn->error);
        }
        
        $projectId = $conn->insert_id;
        
        // Add user as project owner
        $ownerRole = 'Owner';
        $stmt = $conn->prepare("INSERT INTO project_users (project_id, user_id, role) VALUES (?, ?, ?)");
        $stmt->bind_param("iis", $projectId, $userId, $ownerRole);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to add user to project: " . $conn->error);
        }
        
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'project', $projectId, 'created', [
                'name' => $name,
                'theme_color' => $themeColor
            ]);
        }
        
        // Commit transaction
        $conn->commit();
        
        return [
            'success' => true, 
            'message' => 'Project created successfully.',
            'project' => [
                'project_id' => $projectId,
                'name' => $name,
                'description' => $description,
                'theme_color' => $themeColor,
                'created_by' => $userId
            ]
        ];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get a project by ID
 * 
 * @param int $projectId Project ID
 * @return array|false Project data or false if not found
 */
function getProject($projectId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT p.*, u.first_name, u.last_name
        FROM projects p
        JOIN users u ON p.created_by = u.user_id
        WHERE p.project_id = ?
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

/**
 * Check if user has access to a project
 * 
 * @param int $userId User ID
 * @param int $projectId Project ID
 * @return string|false User's role in the project or false if no access
 */
function getUserProjectRole($userId, $projectId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT role
        FROM project_users
        WHERE user_id = ? AND project_id = ?
    ");
    $stmt->bind_param("ii", $userId, $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $row = $result->fetch_assoc();
    return $row['role'];
}

/**
 * Get all projects for a user
 * 
 * @param int $userId User ID
 * @return array Array of projects
 */
function getUserProjects($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT p.*, pu.role
        FROM projects p
        JOIN project_users pu ON p.project_id = pu.project_id
        WHERE pu.user_id = ? AND p.archived = 0
        ORDER BY p.updated_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $projects = [];
    
    while ($row = $result->fetch_assoc()) {
        // Get project statistics
        $projectStats = getProjectStatistics($row['project_id']);
        $row['stats'] = $projectStats;
        $projects[] = $row;
    }
    
    return $projects;
}

/**
 * Get project statistics
 * 
 * @param int $projectId Project ID
 * @return array Project statistics
 */
function getProjectStatistics($projectId) {
    $conn = getDbConnection();
    
    // Get total tickets count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        WHERE d.project_id = ?
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $totalResult = $stmt->get_result();
    $totalRow = $totalResult->fetch_assoc();
    $totalTickets = $totalRow['total'];
    
    // Get open tickets count (all statuses except Complete, Rejected, Ignored)
    $stmt = $conn->prepare("
        SELECT COUNT(*) as open
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        WHERE d.project_id = ? AND t.status NOT IN ('Complete', 'Rejected', 'Ignored')
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $openResult = $stmt->get_result();
    $openRow = $openResult->fetch_assoc();
    $openTickets = $openRow['open'];
    
    // Get completed tickets count
    $stmt = $conn->prepare("
        SELECT COUNT(*) as completed
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        WHERE d.project_id = ? AND t.status = 'Complete'
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $completedResult = $stmt->get_result();
    $completedRow = $completedResult->fetch_assoc();
    $completedTickets = $completedRow['completed'];
    
    return [
        'total_tickets' => $totalTickets,
        'open_tickets' => $openTickets,
        'completed_tickets' => $completedTickets
    ];
}

/**
 * Update a project
 * 
 * @param int $projectId Project ID
 * @param string $name Project name
 * @param string $description Project description
 * @param string $themeColor Project theme color
 * @param int $userId User ID making the update
 * @return array Response with status and message
 */
function updateProject($projectId, $name, $description, $themeColor, $userId) {
    $conn = getDbConnection();
    
    // Validate input
    if (empty($name)) {
        return ['success' => false, 'message' => 'Project name is required.'];
    }
    
    // Validate color format (should be a hex color like #201E5B)
    if (!preg_match('/^#[a-fA-F0-9]{6}$/', $themeColor)) {
        $themeColor = '#201E5B'; // Use default if invalid
    }
    
    // Check if user has permission to update project
    $userRole = getUserProjectRole($userId, $projectId);
    
    if ($userRole !== 'Owner' && $userRole !== 'Project Manager') {
        return ['success' => false, 'message' => 'You do not have permission to update this project.'];
    }
    
    // Update project
    $stmt = $conn->prepare("UPDATE projects SET name = ?, description = ?, theme_color = ? WHERE project_id = ?");
    $stmt->bind_param("sssi", $name, $description, $themeColor, $projectId);
    
    if ($stmt->execute()) {
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'project', $projectId, 'updated', [
                'name' => $name,
                'theme_color' => $themeColor
            ]);
        }
        
        return ['success' => true, 'message' => 'Project updated successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to update project: ' . $conn->error];
    }
}

/**
 * Archive a project
 * 
 * @param int $projectId Project ID
 * @param int $userId User ID making the archive
 * @return array Response with status and message
 */
function archiveProject($projectId, $userId) {
    $conn = getDbConnection();
    
    // Check if user has permission to archive project
    $userRole = getUserProjectRole($userId, $projectId);
    
    if ($userRole !== 'Owner') {
        return ['success' => false, 'message' => 'Only the project owner can archive a project.'];
    }
    
    // Update project
    $archived = 1;
    $stmt = $conn->prepare("UPDATE projects SET archived = ? WHERE project_id = ?");
    $stmt->bind_param("ii", $archived, $projectId);
    
    if ($stmt->execute()) {
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'project', $projectId, 'archived', []);
        }
        
        return ['success' => true, 'message' => 'Project archived successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to archive project: ' . $conn->error];
    }
}

/**
 * Unarchive a project
 * 
 * @param int $projectId Project ID
 * @param int $userId User ID making the unarchive
 * @return array Response with status and message
 */
function unarchiveProject($projectId, $userId) {
    $conn = getDbConnection();
    
    // Check if user has permission to unarchive project
    $userRole = getUserProjectRole($userId, $projectId);
    
    if ($userRole !== 'Owner') {
        return ['success' => false, 'message' => 'Only the project owner can unarchive a project.'];
    }
    
    // Update project
    $archived = 0;
    $stmt = $conn->prepare("UPDATE projects SET archived = ? WHERE project_id = ?");
    $stmt->bind_param("ii", $archived, $projectId);
    
    if ($stmt->execute()) {
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'project', $projectId, 'unarchived', []);
        }
        
        return ['success' => true, 'message' => 'Project unarchived successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to unarchive project: ' . $conn->error];
    }
}

/**
 * Get archived projects for a user
 * 
 * @param int $userId User ID
 * @return array Array of archived projects
 */
function getUserArchivedProjects($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT p.*, pu.role
        FROM projects p
        JOIN project_users pu ON p.project_id = pu.project_id
        WHERE pu.user_id = ? AND p.archived = 1
        ORDER BY p.updated_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $projects = [];
    
    while ($row = $result->fetch_assoc()) {
        $projects[] = $row;
    }
    
    return $projects;
}

/**
 * Add a user to a project
 * 
 * @param int $projectId Project ID
 * @param string $email User email
 * @param string $role User role
 * @param int $addedBy User ID adding the user
 * @return array Response with status and message
 */
function addUserToProject($projectId, $email, $role, $addedBy) {
    $conn = getDbConnection();
    
    // Validate role
    $validRoles = ['Owner', 'Project Manager', 'Tester', 'Reviewer', 'Developer', 'Designer', 'Viewer'];
    if (!in_array($role, $validRoles)) {
        return ['success' => false, 'message' => 'Invalid role.'];
    }
    
    // Check if adder has permission
    $adderRole = getUserProjectRole($addedBy, $projectId);
    if ($adderRole !== 'Owner' && $adderRole !== 'Project Manager') {
        return ['success' => false, 'message' => 'You do not have permission to add users to this project.'];
    }
    
    // Restrict adding owners if not an owner
    if ($role === 'Owner' && $adderRole !== 'Owner') {
        return ['success' => false, 'message' => 'Only an owner can add another owner.'];
    }
    
    // Get user ID from email
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'User with this email does not exist.'];
    }
    
    $row = $result->fetch_assoc();
    $userId = $row['user_id'];
    
    // Check if user is already in project
    $stmt = $conn->prepare("SELECT role FROM project_users WHERE project_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $projectId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['role'] === $role) {
            return ['success' => false, 'message' => 'User already has this role in the project.'];
        }
        
        // Update role
        $stmt = $conn->prepare("UPDATE project_users SET role = ? WHERE project_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $role, $projectId, $userId);
        
        if ($stmt->execute()) {
            // Log activity
            if (LOG_ACTIONS) {
                logActivity($addedBy, $projectId, 'user', $userId, 'role_changed', [
                    'old_role' => $row['role'],
                    'new_role' => $role
                ]);
            }
            
            return ['success' => true, 'message' => 'User role updated successfully.'];
        } else {
            return ['success' => false, 'message' => 'Failed to update user role: ' . $conn->error];
        }
    }
    
    // Add user to project
    $stmt = $conn->prepare("INSERT INTO project_users (project_id, user_id, role) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $projectId, $userId, $role);
    
    if ($stmt->execute()) {
        // Get project info for notification
        $project = getProject($projectId);
        
        // Send email notification if set up
        $userInfo = getUserById($userId);
        
        if ($userInfo) {
            $notificationHtml = '
                <h2>Project Invitation</h2>
                <p>You have been added to the project "' . htmlspecialchars($project['name']) . '" as a ' . $role . '.</p>
                <p>You can access the project by logging in to the Bug Tracker system.</p>
            ';
            
            $notificationText = "Project Invitation\n\n" .
                           "You have been added to the project \"" . $project['name'] . "\" as a " . $role . ".\n\n" .
                           "You can access the project by logging in to the Bug Tracker system.";
            
            sendEmail($userInfo['email'], 'Project Invitation: ' . $project['name'], $notificationHtml, $notificationText);
        }
        
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($addedBy, $projectId, 'user', $userId, 'added', [
                'role' => $role
            ]);
        }
        
        return ['success' => true, 'message' => 'User added to project successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to add user to project: ' . $conn->error];
    }
}

/**
 * Remove a user from a project
 * 
 * @param int $projectId Project ID
 * @param int $userId User ID to remove
 * @param int $removedBy User ID removing the user
 * @return array Response with status and message
 */
function removeUserFromProject($projectId, $userId, $removedBy) {
    $conn = getDbConnection();
    
    // Check if remover has permission
    $removerRole = getUserProjectRole($removedBy, $projectId);
    if ($removerRole !== 'Owner' && $removerRole !== 'Project Manager') {
        return ['success' => false, 'message' => 'You do not have permission to remove users from this project.'];
    }
    
    // Get user's role
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole) {
        return ['success' => false, 'message' => 'User is not part of this project.'];
    }
    
    // Cannot remove owner if not an owner
    if ($userRole === 'Owner' && $removerRole !== 'Owner') {
        return ['success' => false, 'message' => 'Only an owner can remove another owner.'];
    }
    
    // Cannot remove yourself as an owner
    if ($userId === $removedBy && $userRole === 'Owner') {
        // Count other owners
        $stmt = $conn->prepare("
            SELECT COUNT(*) as owner_count
            FROM project_users
            WHERE project_id = ? AND role = 'Owner' AND user_id != ?
        ");
        $stmt->bind_param("ii", $projectId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['owner_count'] === 0) {
            return ['success' => false, 'message' => 'Cannot remove yourself as the only owner. Transfer ownership first.'];
        }
    }
    
    // Remove user from project
    $stmt = $conn->prepare("DELETE FROM project_users WHERE project_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $projectId, $userId);
    
    if ($stmt->execute()) {
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($removedBy, $projectId, 'user', $userId, 'removed', [
                'role' => $userRole
            ]);
        }
        
        return ['success' => true, 'message' => 'User removed from project successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to remove user from project: ' . $conn->error];
    }
}

/**
 * Get project notification preferences for a user
 * 
 * @param int $projectId Project ID
 * @param int $userId User ID
 * @return array|null Notification preferences or null if not set
 */
function getProjectNotificationPreferences($projectId, $userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT notification_preferences
        FROM project_users
        WHERE project_id = ? AND user_id = ?
    ");
    $stmt->bind_param("ii", $projectId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return null;
    }
    
    $row = $result->fetch_assoc();
    
    if ($row['notification_preferences'] === null) {
        // Default preferences
        return [
            'ticket_assigned' => true,
            'ticket_status_changed' => true,
            'ticket_commented' => true,
            'deliverable_created' => true,
            'project_user_added' => true
        ];
    }
    
    return json_decode($row['notification_preferences'], true);
}

/**
 * Update project notification preferences for a user
 * 
 * @param int $projectId Project ID
 * @param int $userId User ID
 * @param array $preferences Notification preferences
 * @return array Response with status and message
 */
function updateProjectNotificationPreferences($projectId, $userId, $preferences) {
    $conn = getDbConnection();
    
    // Check if user is part of project
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole) {
        return ['success' => false, 'message' => 'User is not part of this project.'];
    }
    
    // Convert preferences to JSON
    $preferencesJson = json_encode($preferences);
    
    // Update preferences
    $stmt = $conn->prepare("
        UPDATE project_users
        SET notification_preferences = ?
        WHERE project_id = ? AND user_id = ?
    ");
    $stmt->bind_param("sii", $preferencesJson, $projectId, $userId);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Notification preferences updated successfully.'];
    } else {
        return ['success' => false, 'message' => 'Failed to update notification preferences: ' . $conn->error];
    }
}