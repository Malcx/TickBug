<?php
// ------------------------------------------------------------

// projects.php
// Projects page

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

// Handle different actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

switch ($action) {
    case 'create':
        // Create project page
        $pageTitle = 'Create Project';
        require_once ROOT_PATH . '/views/projects/create.php';
        break;
        
    case 'edit':
        // Edit project page
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setFlashMessage('error', 'Invalid project ID.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $projectId = (int)$_GET['id'];
        $project = getProject($projectId);
        
        if (!$project) {
            setFlashMessage('error', 'Project not found.');
            redirect(BASE_URL . '/projects.php');
        }
        
        // Check if user has permission to edit project
        $userRole = getUserProjectRole($userId, $projectId);
        
        if (!$userRole || ($userRole !== 'Owner' && $userRole !== 'Project Manager')) {
            setFlashMessage('error', 'You do not have permission to edit this project.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $pageTitle = 'Edit Project: ' . $project['name'];
        require_once ROOT_PATH . '/views/projects/edit.php';
        break;
        
    case 'users':
        // Manage project users page
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setFlashMessage('error', 'Invalid project ID.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $projectId = (int)$_GET['id'];
        $project = getProject($projectId);
        
        if (!$project) {
            setFlashMessage('error', 'Project not found.');
            redirect(BASE_URL . '/projects.php');
        }
        
        // Check if user has permission to manage users
        $userRole = getUserProjectRole($userId, $projectId);
        
        if (!$userRole || ($userRole !== 'Owner' && $userRole !== 'Project Manager')) {
            setFlashMessage('error', 'You do not have permission to manage users for this project.');
            redirect(BASE_URL . '/projects.php');
        }
        
        // Get project users
        $projectUsers = getProjectUsers($projectId);
        
        $pageTitle = 'Manage Users: ' . $project['name'];
        require_once ROOT_PATH . '/views/projects/users.php';
        break;
        
    case 'archive':
        // Archive project
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setFlashMessage('error', 'Invalid project ID.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $projectId = (int)$_GET['id'];
        $project = getProject($projectId);
        
        if (!$project) {
            setFlashMessage('error', 'Project not found.');
            redirect(BASE_URL . '/projects.php');
        }
        
        // Check if user has permission to archive project
        $userRole = getUserProjectRole($userId, $projectId);
        
        if (!$userRole || $userRole !== 'Owner') {
            setFlashMessage('error', 'Only the project owner can archive a project.');
            redirect(BASE_URL . '/projects.php');
        }
        
        // Archive project
        $result = archiveProject($projectId, $userId);
        
        if ($result['success']) {
            setFlashMessage('success', 'Project archived successfully.');
        } else {
            setFlashMessage('error', $result['message']);
        }
        
        redirect(BASE_URL . '/projects.php');
        break;
        
    case 'unarchive':
        // Unarchive project
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setFlashMessage('error', 'Invalid project ID.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $projectId = (int)$_GET['id'];
        $project = getProject($projectId);
        
        if (!$project) {
            setFlashMessage('error', 'Project not found.');
            redirect(BASE_URL . '/projects.php');
        }
        
        // Check if user has permission to unarchive project
        $userRole = getUserProjectRole($userId, $projectId);
        
        if (!$userRole || $userRole !== 'Owner') {
            setFlashMessage('error', 'Only the project owner can unarchive a project.');
            redirect(BASE_URL . '/projects.php');
        }
        
        // Unarchive project
        $result = unarchiveProject($projectId, $userId);
        
        if ($result['success']) {
            setFlashMessage('success', 'Project unarchived successfully.');
        } else {
            setFlashMessage('error', $result['message']);
        }
        
        redirect(BASE_URL . '/projects.php');
        break;
        
    case 'archived':
        // Show archived projects
        $archivedProjects = getUserArchivedProjects($userId);
        $pageTitle = 'Archived Projects';
        require_once ROOT_PATH . '/views/projects/archived.php';
        break;
        
    default:
        // View single project if ID is provided
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $projectId = (int)$_GET['id'];
            $project = getProject($projectId);
            
            if (!$project) {
                setFlashMessage('error', 'Project not found.');
                redirect(BASE_URL . '/projects.php');
            }
            
            // Check if user has access to this project
            $userRole = getUserProjectRole($userId, $projectId);
            
            if (!$userRole) {
                setFlashMessage('error', 'You do not have access to this project.');
                redirect(BASE_URL . '/projects.php');
            }
            
            $pageTitle = $project['name'];
            require_once ROOT_PATH . '/views/projects/view.php';
        } else {
            // Show all user projects
            $projects = getUserProjects($userId);
            $pageTitle = 'My Projects';
            require_once ROOT_PATH . '/views/projects/list.php';
        }
        break;
}