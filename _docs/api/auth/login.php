<?php
// api/auth/login.php
// Login API endpoint

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
        redirect(BASE_URL . '/login.php');
    }
}

// Get form data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

// Validate form data
if (empty($email) || empty($password)) {
    $response = ['success' => false, 'message' => 'Email and password are required.'];
    
    if (isAjaxRequest()) {
        sendJsonResponse($response);
    } else {
        setFlashMessage('error', $response['message']);
        redirect(BASE_URL . '/login.php');
    }
}

// Attempt login
$result = loginUser($email, $password);

// Return response
if (isAjaxRequest()) {
    sendJsonResponse($result);
} else {
    if ($result['success']) {
        setFlashMessage('success', 'Login successful.');
        redirect(BASE_URL . '/projects.php');
    } else {
        setFlashMessage('error', $result['message']);
        redirect(BASE_URL . '/login.php');
    }
}

// ------------------------------------------------------------

