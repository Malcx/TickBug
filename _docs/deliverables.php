<?php

// ------------------------------------------------------------

// deliverables.php
// Deliverables page

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
        // Create deliverable page
        if (!isset($_GET['project_id']) || empty($_GET['project_id'])) {
            setFlashMessage('error', 'Project ID is required to create a deliverable.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $projectId = (int)$_GET['project_id'];
        $project = getProject($projectId);
        
        if (!$project) {
            setFlashMessage('error', 'Project not found.');
            redirect(BASE_URL . '/projects.php');
        }
        
        // Check if user has permission to create deliverables
        $userRole = getUserProjectRole($userId, $projectId);
        
        if (!$userRole || $userRole === 'Viewer' || $userRole === 'Tester') {
            setFlashMessage('error', 'You do not have permission to create deliverables.');
            redirect(BASE_URL . '/projects.php?id=' . $projectId);
        }
        
        $pageTitle = 'Create Deliverable for ' . $project['name'];
        require_once ROOT_PATH . '/views/deliverables/create.php';
        break;
        
    case 'edit':
        // Edit deliverable page
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setFlashMessage('error', 'Invalid deliverable ID.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $deliverableId = (int)$_GET['id'];
        $deliverable = getDeliverable($deliverableId);
        
        if (!$deliverable) {
            setFlashMessage('error', 'Deliverable not found.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $projectId = $deliverable['project_id'];
        
        // Check if user has permission to edit deliverables
        $userRole = getUserProjectRole($userId, $projectId);
        
        if (!$userRole || $userRole === 'Viewer' || $userRole === 'Tester') {
            setFlashMessage('error', 'You do not have permission to edit deliverables.');
            redirect(BASE_URL . '/projects.php?id=' . $projectId);
        }
        
        $pageTitle = 'Edit Deliverable: ' . $deliverable['name'];
        require_once ROOT_PATH . '/views/deliverables/edit.php';
        break;
        
    default:
        // Redirect to projects page
        redirect(BASE_URL . '/projects.php');
        break;
}