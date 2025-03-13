<?php

// ------------------------------------------------------------

// api/projects/users.php
// Project users API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to manage project users.'];
    sendJsonResponse($response);
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response = ['success' => false, 'message' => 'Invalid request method.'];
    sendJsonResponse($response);
}

// Get current user ID
$userId = getCurrentUserId();

// Get form data
$projectId = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

// Validate form data
if (empty($projectId)) {
    $response = ['success' => false, 'message' => 'Project ID is required.'];
    sendJsonResponse($response);
}

// Check if user has permission to manage users
$userRole = getUserProjectRole($userId, $projectId);

if (!$userRole || ($userRole !== 'Owner' && $userRole !== 'Project Manager')) {
    $response = ['success' => false, 'message' => 'You do not have permission to manage users for this project.'];
    sendJsonResponse($response);
}

// Handle different actions
switch ($action) {
    case 'add':
        // Add user to project
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $role = isset($_POST['role']) ? $_POST['role'] : '';
        
        if (empty($email)) {
            $response = ['success' => false, 'message' => 'Email is required.'];
            sendJsonResponse($response);
        }
        
        if (empty($role)) {
            $response = ['success' => false, 'message' => 'Role is required.'];
            sendJsonResponse($response);
        }
        
        $result = addUserToProject($projectId, $email, $role, $userId);
        sendJsonResponse($result);
        break;
        
    case 'remove':
        // Remove user from project
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        
        if (empty($targetUserId)) {
            $response = ['success' => false, 'message' => 'User ID is required.'];
            sendJsonResponse($response);
        }
        
        $result = removeUserFromProject($projectId, $targetUserId, $userId);
        sendJsonResponse($result);
        break;
        
    case 'change_role':
        // Change user role
        $targetUserId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
        $role = isset($_POST['role']) ? $_POST['role'] : '';
        
        if (empty($targetUserId)) {
            $response = ['success' => false, 'message' => 'User ID is required.'];
            sendJsonResponse($response);
        }
        
        if (empty($role)) {
            $response = ['success' => false, 'message' => 'Role is required.'];
            sendJsonResponse($response);
        }
        
        // Use addUserToProject for role change (it handles both adding and updating)
        $targetUser = getUserById($targetUserId);
        
        if (!$targetUser) {
            $response = ['success' => false, 'message' => 'User not found.'];
            sendJsonResponse($response);
        }
        
        $result = addUserToProject($projectId, $targetUser['email'], $role, $userId);
        sendJsonResponse($result);
        break;
        
    case 'list':
        // List project users
        $projectUsers = getProjectUsers($projectId);
        
        $response = [
            'success' => true,
            'users' => $projectUsers
        ];
        
        sendJsonResponse($response);
        break;
        
    default:
        $response = ['success' => false, 'message' => 'Invalid action.'];
        sendJsonResponse($response);
        break;
}