<?php
// includes/reports.php
// Report generation functions

/**
 * Generate project status report
 * 
 * @param int $projectId Project ID
 * @return array Project status report data
 */
function generateProjectStatusReport($projectId) {
    $conn = getDbConnection();
    
    // Get project info
    $project = getProject($projectId);
    
    if (!$project) {
        return ['success' => false, 'message' => 'Project not found.'];
    }
    
    // Get project statistics
    $stats = getProjectStatistics($projectId);
    
    // Get tickets by status
    $stmt = $conn->prepare("
        SELECT s.name as status, COUNT(*) as count
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        JOIN statuses s ON t.status_id = s.status_id
        WHERE d.project_id = ?
        GROUP BY t.status_id, s.name
        ORDER BY t.status_id
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ticketsByStatus = [];
    
    while ($row = $result->fetch_assoc()) {
        $ticketsByStatus[$row['status']] = $row['count'];
    }
    
    // Get tickets by priority
    $stmt = $conn->prepare("
        SELECT p.name as priority, COUNT(*) as count
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        JOIN priorities p ON t.priority_id = p.priority_id
        WHERE d.project_id = ?
        GROUP BY t.priority_id, p.name
        ORDER BY t.priority_id
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ticketsByPriority = [];
    
    while ($row = $result->fetch_assoc()) {
        $ticketsByPriority[$row['priority']] = $row['count'];
    }
    
    // Get tickets by assignee
    $stmt = $conn->prepare("
        SELECT 
            CASE WHEN t.assigned_to IS NULL THEN 'Unassigned' ELSE CONCAT(u.first_name, ' ', u.last_name) END as assignee,
            COUNT(*) as count
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        LEFT JOIN users u ON t.assigned_to = u.user_id
        WHERE d.project_id = ?
        GROUP BY assignee
        ORDER BY count DESC
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ticketsByAssignee = [];
    
    while ($row = $result->fetch_assoc()) {
        $ticketsByAssignee[$row['assignee']] = $row['count'];
    }
    
    // Get tickets by deliverable
    $stmt = $conn->prepare("
        SELECT d.name as deliverable, COUNT(*) as count
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        WHERE d.project_id = ?
        GROUP BY d.deliverable_id
        ORDER BY count DESC
    ");
    $stmt->bind_param("i", $projectId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $ticketsByDeliverable = [];
    
    while ($row = $result->fetch_assoc()) {
        $ticketsByDeliverable[$row['deliverable']] = $row['count'];
    }
    
    // Get recent activity
    $activityLog = getProjectActivityLog($projectId, 10);
    
    return [
        'success' => true,
        'project' => $project,
        'stats' => $stats,
        'tickets_by_status' => $ticketsByStatus,
        'tickets_by_priority' => $ticketsByPriority,
        'tickets_by_assignee' => $ticketsByAssignee,
        'tickets_by_deliverable' => $ticketsByDeliverable,
        'activity' => $activityLog
    ];
}

/**
 * Generate user activity report
 * 
 * @param int $userId User ID
 * @param int $projectId Project ID (optional - filter by project)
 * @param string $startDate Start date (optional - format: YYYY-MM-DD)
 * @param string $endDate End date (optional - format: YYYY-MM-DD)
 * @return array User activity report data
 */
function generateUserActivityReport($userId, $projectId = null, $startDate = null, $endDate = null) {
    $conn = getDbConnection();
    
    // Get user info
    $user = getUserById($userId);
    
    if (!$user) {
        return ['success' => false, 'message' => 'User not found.'];
    }
    
    // Start building query for tickets created
    $ticketsCreatedSql = "
        SELECT t.*, d.name as deliverable_name, p.name as project_name
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        JOIN projects p ON d.project_id = p.project_id
        WHERE t.created_by = ?
    ";
    
    // Start building query for tickets completed (status_id = 6 is 'Complete')
    $ticketsCompletedSql = "
        SELECT t.*, d.name as deliverable_name, p.name as project_name
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        JOIN projects p ON d.project_id = p.project_id
        WHERE t.assigned_to = ? AND t.status_id = 6
    ";
    
    // Start building query for comments
    $commentsSql = "
        SELECT c.*, t.title as ticket_title, p.name as project_name
        FROM comments c
        JOIN tickets t ON c.ticket_id = t.ticket_id
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        JOIN projects p ON d.project_id = p.project_id
        WHERE c.user_id = ?
    ";
    
    // Add project filter if provided
    if ($projectId) {
        $projectFilter = " AND d.project_id = ?";
        $ticketsCreatedSql .= $projectFilter;
        $ticketsCompletedSql .= $projectFilter;
        $commentsSql .= $projectFilter;
    }
    
    // Add date filters if provided
    if ($startDate) {
        $dateFilter = " AND DATE(t.created_at) >= ?";
        $ticketsCreatedSql .= $dateFilter;
        $ticketsCompletedSql .= $dateFilter;
        $commentsSql .= str_replace("t.", "c.", $dateFilter);
    }
    
    if ($endDate) {
        $dateFilter = " AND DATE(t.created_at) <= ?";
        $ticketsCreatedSql .= $dateFilter;
        $ticketsCompletedSql .= $dateFilter;
        $commentsSql .= str_replace("t.", "c.", $dateFilter);
    }
    
    // Order results
    $ticketsCreatedSql .= " ORDER BY t.created_at DESC";
    $ticketsCompletedSql .= " ORDER BY t.updated_at DESC";
    $commentsSql .= " ORDER BY c.created_at DESC";
    
    // Prepare and execute tickets created query
    $stmt = $conn->prepare($ticketsCreatedSql);
    
    $bindParams = [$userId];
    $types = "i";
    
    if ($projectId) {
        $bindParams[] = $projectId;
        $types .= "i";
    }
    
    if ($startDate) {
        $bindParams[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate) {
        $bindParams[] = $endDate;
        $types .= "s";
    }
    
    // Dynamic binding
    $bindParamsArray = array_merge([$types], $bindParams);
    $stmt->bind_param(...$bindParamsArray);
    
    $stmt->execute();
    $ticketsCreatedResult = $stmt->get_result();
    
    $ticketsCreated = [];
    while ($row = $ticketsCreatedResult->fetch_assoc()) {
        $ticketsCreated[] = $row;
    }
    
    // Prepare and execute tickets completed query
    $stmt = $conn->prepare($ticketsCompletedSql);
    
    $bindParams = [$userId];
    $types = "i";
    
    if ($projectId) {
        $bindParams[] = $projectId;
        $types .= "i";
    }
    
    if ($startDate) {
        $bindParams[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate) {
        $bindParams[] = $endDate;
        $types .= "s";
    }
    
    // Dynamic binding
    $bindParamsArray = array_merge([$types], $bindParams);
    $stmt->bind_param(...$bindParamsArray);
    
    $stmt->execute();
    $ticketsCompletedResult = $stmt->get_result();
    
    $ticketsCompleted = [];
    while ($row = $ticketsCompletedResult->fetch_assoc()) {
        $ticketsCompleted[] = $row;
    }
    
    // Prepare and execute comments query
    $stmt = $conn->prepare($commentsSql);
    
    $bindParams = [$userId];
    $types = "i";
    
    if ($projectId) {
        $bindParams[] = $projectId;
        $types .= "i";
    }
    
    if ($startDate) {
        $bindParams[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate) {
        $bindParams[] = $endDate;
        $types .= "s";
    }
    
    // Dynamic binding
    $bindParamsArray = array_merge([$types], $bindParams);
    $stmt->bind_param(...$bindParamsArray);
    
    $stmt->execute();
    $commentsResult = $stmt->get_result();
    
    $comments = [];
    while ($row = $commentsResult->fetch_assoc()) {
        $comments[] = $row;
    }
    
    // Get activity summary
    $createdCount = count($ticketsCreated);
    $completedCount = count($ticketsCompleted);
    $commentsCount = count($comments);
    
    return [
        'success' => true,
        'user' => $user,
        'created_count' => $createdCount,
        'completed_count' => $completedCount,
        'comments_count' => $commentsCount,
        'tickets_created' => $ticketsCreated,
        'tickets_completed' => $ticketsCompleted,
        'comments' => $comments
    ];
}

/**
 * Generate overall system report
 * 
 * @return array System report data
 */
function generateSystemReport() {
    $conn = getDbConnection();
    
    // Total users
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalUsers = $row['count'];
    
    // Total projects
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM projects WHERE archived = 0");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $activeProjects = $row['count'];
    
    // Total tickets
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tickets");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $totalTickets = $row['count'];
    
    // Open tickets
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM tickets 
        WHERE status_id NOT IN (6, 7, 8)
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $openTickets = $row['count'];
    
    // Critical tickets
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM tickets 
        WHERE priority_id = 1 AND status_id NOT IN (6, 7, 8)
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $criticalTickets = $row['count'];
    
    // Most active projects
    $stmt = $conn->prepare("
        SELECT p.project_id, p.name, COUNT(t.ticket_id) as ticket_count
        FROM projects p
        JOIN deliverables d ON p.project_id = d.project_id
        JOIN tickets t ON d.deliverable_id = t.deliverable_id
        WHERE p.archived = 0
        GROUP BY p.project_id
        ORDER BY ticket_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $mostActiveProjects = [];
    while ($row = $result->fetch_assoc()) {
        $mostActiveProjects[] = $row;
    }
    
    // Most active users
    $stmt = $conn->prepare("
        SELECT u.user_id, u.first_name, u.last_name, COUNT(a.log_id) as activity_count
        FROM users u
        JOIN activity_log a ON u.user_id = a.user_id
        WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY u.user_id
        ORDER BY activity_count DESC
        LIMIT 5
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $mostActiveUsers = [];
    while ($row = $result->fetch_assoc()) {
        $mostActiveUsers[] = $row;
    }
    
    // Recent activity
    $stmt = $conn->prepare("
        SELECT a.*, u.first_name, u.last_name, p.name as project_name
        FROM activity_log a
        JOIN users u ON a.user_id = u.user_id
        JOIN projects p ON a.project_id = p.project_id
        ORDER BY a.created_at DESC
        LIMIT 20
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $recentActivity = [];
    while ($row = $result->fetch_assoc()) {
        // Convert JSON details to array
        $row['details'] = json_decode($row['details'], true);
        
        // Add user name
        $row['user_name'] = $row['first_name'] . ' ' . $row['last_name'];
        
        // Add formatted timestamp
        $row['formatted_time'] = date('M j, Y g:i A', strtotime($row['created_at']));
        
        // Add formatted action description
        $row['description'] = formatActivityDescription($row);
        
        $recentActivity[] = $row;
    }
    
    return [
        'success' => true,
        'total_users' => $totalUsers,
        'active_projects' => $activeProjects,
        'total_tickets' => $totalTickets,
        'open_tickets' => $openTickets,
        'critical_tickets' => $criticalTickets,
        'most_active_projects' => $mostActiveProjects,
        'most_active_users' => $mostActiveUsers,
        'recent_activity' => $recentActivity
    ];
}

/**
 * Generate tickets by status report
 * 
 * @param int $projectId Project ID (optional)
 * @return array Tickets by status report data
 */
function generateTicketsByStatusReport($projectId = null) {
    $conn = getDbConnection();
    
    $projectFilter = '';
    $params = [];
    $types = '';
    
    if ($projectId) {
        $projectFilter = " AND d.project_id = ?";
        $params[] = $projectId;
        $types = "i";
    }
    
    $sql = "
        SELECT s.name as status,
               COUNT(*) as count,
               SUM(CASE WHEN t.priority_id = 1 THEN 1 ELSE 0 END) as critical_count,
               SUM(CASE WHEN t.priority_id = 2 THEN 1 ELSE 0 END) as important_count,
               SUM(CASE WHEN t.priority_id = 3 THEN 1 ELSE 0 END) as nice_count,
               SUM(CASE WHEN t.priority_id = 4 THEN 1 ELSE 0 END) as feature_count,
               SUM(CASE WHEN t.priority_id = 5 THEN 1 ELSE 0 END) as low_count
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        JOIN statuses s ON t.status_id = s.status_id
        WHERE 1=1 $projectFilter
        GROUP BY t.status_id, s.name
        ORDER BY t.status_id
    ";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $bindParamsArray = array_merge([$types], $params);
        $stmt->bind_param(...$bindParamsArray);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $statusData = [];
    while ($row = $result->fetch_assoc()) {
        $statusData[] = $row;
    }
    
    // Get project info if project ID was provided
    $project = null;
    if ($projectId) {
        $project = getProject($projectId);
    }
    
    return [
        'success' => true,
        'project' => $project,
        'status_data' => $statusData
    ];
}

/**
 * Generate user productivity report
 * 
 * @param int $projectId Project ID (optional)
 * @param string $startDate Start date (optional - format: YYYY-MM-DD)
 * @param string $endDate End date (optional - format: YYYY-MM-DD)
 * @return array User productivity report data
 */
function generateUserProductivityReport($projectId = null, $startDate = null, $endDate = null) {
    $conn = getDbConnection();
    
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if ($projectId) {
        $whereConditions[] = "d.project_id = ?";
        $params[] = $projectId;
        $types .= "i";
    }
    
    if ($startDate) {
        $whereConditions[] = "DATE(t.updated_at) >= ?";
        $params[] = $startDate;
        $types .= "s";
    }
    
    if ($endDate) {
        $whereConditions[] = "DATE(t.updated_at) <= ?";
        $params[] = $endDate;
        $types .= "s";
    }
    
    $whereClause = '';
    if (!empty($whereConditions)) {
        $whereClause = " WHERE " . implode(" AND ", $whereConditions);
    }
    
    $sql = "
        SELECT
            u.user_id,
            u.first_name,
            u.last_name,
            COUNT(DISTINCT t.ticket_id) as total_tickets,
            SUM(CASE WHEN t.status_id = 6 THEN 1 ELSE 0 END) as completed_tickets,
            SUM(CASE WHEN t.status_id IN (1, 2, 3, 4, 5) THEN 1 ELSE 0 END) as open_tickets,
            SUM(CASE WHEN t.priority_id = 1 THEN 1 ELSE 0 END) as critical_tickets,
            SUM(CASE WHEN t.priority_id = 2 THEN 1 ELSE 0 END) as important_tickets,
            (SELECT COUNT(*) FROM comments c WHERE c.user_id = u.user_id) as comment_count
        FROM tickets t
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        JOIN users u ON t.assigned_to = u.user_id
        $whereClause
        GROUP BY u.user_id
        ORDER BY completed_tickets DESC, total_tickets DESC
    ";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $bindParamsArray = array_merge([$types], $params);
        $stmt->bind_param(...$bindParamsArray);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $userData = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate completion rate
        $row['completion_rate'] = ($row['total_tickets'] > 0) 
            ? round(($row['completed_tickets'] / $row['total_tickets']) * 100, 1) 
            : 0;
            
        $userData[] = $row;
    }
    
    // Get project info if project ID was provided
    $project = null;
    if ($projectId) {
        $project = getProject($projectId);
    }
    
    return [
        'success' => true,
        'project' => $project,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'user_data' => $userData
    ];
}