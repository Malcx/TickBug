<?php
// api/auth/update-profile.php
// Update profile API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    $response = ['success' => false, 'message' => 'You must be logged in to update your profile.'];
    
    if (isAjaxRequest()) {
        sendJsonResponse($response);
    } else {
        setFlashMessage('error', $response['message']);
        redirect(BASE_URL . '/login.php');
    }
}

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Return error for non-POST requests
    $response = ['success' => false, 'message' => 'Invalid request method.'];
    
    if (isAjaxRequest()) {
        sendJsonResponse($response);
    } else {
        setFlashMessage('error', $response['message']);
        redirect(BASE_URL . '/profile.php');
    }
}

// Get user ID
$userId = getCurrentUserId();

// Get form data
$firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$currentPassword = isset($_POST['current_password']) ? $_POST['current_password'] : '';
$newPassword = isset($_POST['new_password']) ? $_POST['new_password'] : '';
$confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Validate form data
if (empty($firstName) || empty($lastName) || empty($email)) {
    $response = ['success' => false, 'message' => 'Name and email are required.'];
    
    if (isAjaxRequest()) {
        sendJsonResponse($response);
    } else {
        setFlashMessage('error', $response['message']);
        redirect(BASE_URL . '/profile.php');
    }
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response = ['success' => false, 'message' => 'Invalid email format.'];
    
    if (isAjaxRequest()) {
        sendJsonResponse($response);
    } else {
        setFlashMessage('error', $response['message']);
        redirect(BASE_URL . '/profile.php');
    }
}

// Prepare update data
$updateData = [
    'first_name' => $firstName,
    'last_name' => $lastName,
    'email' => $email
];

// Handle password change if provided
if (!empty($newPassword)) {
    // Validate current password
    if (empty($currentPassword)) {
        $response = ['success' => false, 'message' => 'Current password is required to set a new password.'];
        
        if (isAjaxRequest()) {
            sendJsonResponse($response);
        } else {
            setFlashMessage('error', $response['message']);
            redirect(BASE_URL . '/profile.php');
        }
    }
    
    // Validate new password
    if (strlen($newPassword) < 8) {
        $response = ['success' => false, 'message' => 'New password must be at least 8 characters long.'];
        
        if (isAjaxRequest()) {
            sendJsonResponse($response);
        } else {
            setFlashMessage('error', $response['message']);
            redirect(BASE_URL . '/profile.php');
        }
    }
    
    // Check if passwords match
    if ($newPassword !== $confirmPassword) {
        $response = ['success' => false, 'message' => 'New passwords do not match.'];
        
        if (isAjaxRequest()) {
            sendJsonResponse($response);
        } else {
            setFlashMessage('error', $response['message']);
            redirect(BASE_URL . '/profile.php');
        }
    }
    
    // Verify current password
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $response = ['success' => false, 'message' => 'User not found.'];
        
        if (isAjaxRequest()) {
            sendJsonResponse($response);
        } else {
            setFlashMessage('error', $response['message']);
            redirect(BASE_URL . '/profile.php');
        }
    }
    
    $userData = $result->fetch_assoc();
    
    if (!password_verify($currentPassword, $userData['password'])) {
        $response = ['success' => false, 'message' => 'Current password is incorrect.'];
        
        if (isAjaxRequest()) {
            sendJsonResponse($response);
        } else {
            setFlashMessage('error', $response['message']);
            redirect(BASE_URL . '/profile.php');
        }
    }
    
    // Add new password to update data
    $updateData['password'] = $newPassword;
}

// Update user profile
$result = updateUserProfile($userId, $updateData);

// Return response
if (isAjaxRequest()) {
    sendJsonResponse($result);
} else {
    if ($result['success']) {
        setFlashMessage('success', $result['message']);
    } else {
        setFlashMessage('error', $result['message']);
    }
    
    redirect(BASE_URL . '/profile.php');
}