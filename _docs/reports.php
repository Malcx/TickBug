<?php

// ------------------------------------------------------------

// reports.php
// Reports page

// Include helper functions
require_once 'includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    // Redirect to login page
    setFlashMessage('error', 'You must be logged in to access this page.');
    redirect(BASE_URL . '/login.php');
}

// Get current user ID
$userId = getCurrentUserId();

// Get report type
$reportType = isset($_GET['type']) ? $_GET['type'] : 'overview';
$projectId = isset($_GET['project_id']) ? (int)$_GET['project_id'] : null;

// If project ID is provided, check if user has access
if ($projectId) {
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole) {
        setFlashMessage('error', 'You do not have access to this project.');
        redirect(BASE_URL . '/projects.php');
    }
    
    // Check if user has permission to view reports
    if ($userRole !== 'Owner' && $userRole !== 'Project Manager' && $userRole !== 'Reviewer') {
        setFlashMessage('error', 'You do not have permission to view reports for this project.');
        redirect(BASE_URL . '/projects.php?id=' . $projectId);
    }
}

// Generate report based on type
switch ($reportType) {
    case 'project':
        // Project status report
        if (!$projectId) {
            setFlashMessage('error', 'Project ID is required for project status report.');
            redirect(BASE_URL . '/reports.php');
        }
        
        $report = generateProjectStatusReport($projectId);
        $pageTitle = 'Project Status Report: ' . $report['project']['name'];
        require_once ROOT_PATH . '/views/reports/project.php';
        break;
        
    case 'user':
        // User activity report
        $targetUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $userId;
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
        
        $report = generateUserActivityReport($targetUserId, $projectId, $startDate, $endDate);
        $pageTitle = 'User Activity Report: ' . $report['user']['first_name'] . ' ' . $report['user']['last_name'];
        require_once ROOT_PATH . '/views/reports/user.php';
        break;
        
    case 'tickets':
        // Tickets by status report
        $report = generateTicketsByStatusReport($projectId);
        $pageTitle = $projectId ? 'Ticket Status Report: ' . $report['project']['name'] : 'All Tickets Status Report';
        require_once ROOT_PATH . '/views/reports/tickets.php';
        break;
        
    case 'productivity':
        // User productivity report
        $startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
        $endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;
        
        $report = generateUserProductivityReport($projectId, $startDate, $endDate);
        $pageTitle = $projectId ? 'Team Productivity Report: ' . $report['project']['name'] : 'Overall Team Productivity Report';
        require_once ROOT_PATH . '/views/reports/productivity.php';
        break;
        
    default:
        // System overview report
        $report = generateSystemReport();
        $pageTitle = 'System Overview Report';
        require_once ROOT_PATH . '/views/reports/overview.php';
        break;
}