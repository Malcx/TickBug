<?php


// ------------------------------------------------------------

// tickets.php
// Tickets page

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
        // Create ticket page
        if (!isset($_GET['deliverable_id']) || empty($_GET['deliverable_id'])) {
            setFlashMessage('error', 'Deliverable ID is required to create a ticket.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $deliverableId = (int)$_GET['deliverable_id'];
        $deliverable = getDeliverable($deliverableId);
        
        if (!$deliverable) {
            setFlashMessage('error', 'Deliverable not found.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $projectId = $deliverable['project_id'];
        
        // Check if user has permission to create tickets
        $userRole = getUserProjectRole($userId, $projectId);
        
        if (!$userRole || $userRole === 'Viewer') {
            setFlashMessage('error', 'You do not have permission to create tickets.');
            redirect(BASE_URL . '/projects.php?id=' . $projectId);
        }
        
        $pageTitle = 'Create Ticket for ' . $deliverable['name'];
        require_once ROOT_PATH . '/views/tickets/create.php';
        break;
        
    case 'edit':
        // Edit ticket page
        if (!isset($_GET['id']) || empty($_GET['id'])) {
            setFlashMessage('error', 'Invalid ticket ID.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $ticketId = (int)$_GET['id'];
        $ticket = getTicket($ticketId);
        
        if (!$ticket) {
            setFlashMessage('error', 'Ticket not found.');
            redirect(BASE_URL . '/projects.php');
        }
        
        $projectId = $ticket['project_id'];
        
        // Check if user has permission to edit tickets
        $userRole = getUserProjectRole($userId, $projectId);
        
        if (!$userRole || $userRole === 'Viewer' || 
            ($userRole === 'Tester' && $ticket['created_by'] !== $userId)) {
            setFlashMessage('error', 'You do not have permission to edit this ticket.');
            redirect(BASE_URL . '/tickets.php?id=' . $ticketId);
        }
        
        $pageTitle = 'Edit Ticket: ' . $ticket['title'];
        require_once ROOT_PATH . '/views/tickets/edit.php';
        break;
        
    default:
        // View single ticket if ID is provided
        if (isset($_GET['id']) && !empty($_GET['id'])) {
            $ticketId = (int)$_GET['id'];
            $ticket = getTicket($ticketId);
            
            if (!$ticket) {
                setFlashMessage('error', 'Ticket not found.');
                redirect(BASE_URL . '/projects.php');
            }
            
            $projectId = $ticket['project_id'];
            
            // Check if user has access to this project
            $userRole = getUserProjectRole($userId, $projectId);
            
            if (!$userRole) {
                setFlashMessage('error', 'You do not have access to this ticket.');
                redirect(BASE_URL . '/projects.php');
            }
            
            $pageTitle = $ticket['title'];
            require_once ROOT_PATH . '/views/tickets/view.php';
        } else {
            // Redirect to projects page
            redirect(BASE_URL . '/projects.php');
        }
        break;
}