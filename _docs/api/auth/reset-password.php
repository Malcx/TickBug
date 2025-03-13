<?php
// api/auth/reset-password.php
// Password reset API endpoint

// Include helper functions
require_once '../../includes/helpers.php';

// Start session
startSession();

// Check request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Return error for non-POST requests
    $response = ['success' => false, 'message' => 'Invalid request method.'];
    
    if (isAjaxRequest()) {
        sendJsonResponse($response);
    } else {
        setFlashMessage('error', $response['message']);
        redirect(BASE_URL . '/forgot-password.php');
    }
}

// Check if token is provided (reset password)
if (isset($_POST['token'])) {
    // Get form data
    $token = $_POST['token'];
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validate form data
    if (empty($password) || empty($confirmPassword)) {
        $response = ['success' => false, 'message' => 'All fields are required.'];
        
        if (isAjaxRequest()) {
            sendJsonResponse($response);
        } else {
            setFlashMessage('error', $response['message']);
            redirect(BASE_URL . '/reset-password.php?token=' . $token);
        }
    }
    
    // Validate password
    if (strlen($password) < 8) {
        $response = ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        
        if (isAjaxRequest()) {
            sendJsonResponse($response);
        } else {
            setFlashMessage('error', $response['message']);
            redirect(BASE_URL . '/reset-password.php?token=' . $token);
        }
    }
    
    // Check if passwords match
    if ($password !== $confirmPassword) {
        $response = ['success' => false, 'message' => 'Passwords do not match.'];
        
        if (isAjaxRequest()) {
            sendJsonResponse($response);
        } else {
            setFlashMessage('error', $response['message']);
            redirect(BASE_URL . '/reset-password.php?token=' . $token);
        }
    }
    
    // Attempt password reset
    $result = resetPassword($token, $password);
    
    // Return response
    if (isAjaxRequest()) {
        sendJsonResponse($result);
    } else {
        if ($result['success']) {
            setFlashMessage('success', $result['message']);
            redirect(BASE_URL . '/login.php');
        } else {
            setFlashMessage('error', $result['message']);
            redirect(BASE_URL . '/reset-password.php?token=' . $token);
        }
    }
} else {
    // Request password reset (forgot password)
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    
    // Validate form data
    if (empty($email)) {
        $response = ['success' => false, 'message' => 'Email is required.'];
        
        if (isAjaxRequest()) {
            sendJsonResponse($response);
        } else {
            setFlashMessage('error', $response['message']);
            redirect(BASE_URL . '/forgot-password.php');
        }
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response = ['success' => false, 'message' => 'Invalid email format.'];
        
        if (isAjaxRequest()) {
            sendJsonResponse($response);
        } else {
            setFlashMessage('error', $response['message']);
            redirect(BASE_URL . '/forgot-password.php');
        }
    }
    
    // Attempt to create password reset token
    $result = createPasswordResetToken($email);
    
    // Return response
    if (isAjaxRequest()) {
        sendJsonResponse($result);
    } else {
        setFlashMessage('success', $result['message']);
        redirect(BASE_URL . '/login.php');
    }
}

// ------------------------------------------------------------

