<?php
// api/auth/register.php
// Registration API endpoint

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
        redirect(BASE_URL . '/register.php');
    }
}

// Get form data
$firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
$lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$confirmPassword = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// Validate form data
if (empty($firstName) || empty($lastName) || empty($email) || empty($password) || empty($confirmPassword)) {
    $response = ['success' => false, 'message' => 'All fields are required.'];
    
    if (isAjaxRequest()) {
        sendJsonResponse($response);
    } else {
        setFlashMessage('error', $response['message']);
        redirect(BASE_URL . '/register.php');
    }
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $response = ['success' => false, 'message' => 'Invalid email format.'];
    
    if (isAjaxRequest()) {
        sendJsonResponse($response);
    } else {
        setFlashMessage('error', $response['message']);
        redirect(BASE_URL . '/register.php');
    }
}

// Validate password
if (strlen($password) < 8) {
    $response = ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
    
    if (isAjaxRequest()) {
        sendJsonResponse($response);
    } else {
        setFlashMessage('error', $response['message']);
        redirect(BASE_URL . '/register.php');
    }
}

// Check if passwords match
if ($password !== $confirmPassword) {
    $response = ['success' => false, 'message' => 'Passwords do not match.'];
    
    if (isAjaxRequest()) {
        sendJsonResponse($response);
    } else {
        setFlashMessage('error', $response['message']);
        redirect(BASE_URL . '/register.php');
    }
}

// Attempt registration
$result = registerUser($email, $password, $firstName, $lastName);

// Return response
if (isAjaxRequest()) {
    sendJsonResponse($result);
} else {
    if ($result['success']) {
        setFlashMessage('success', 'Registration successful. Please log in.');
        redirect(BASE_URL . '/login.php');
    } else {
        setFlashMessage('error', $result['message']);
        redirect(BASE_URL . '/register.php');
    }
}

// ------------------------------------------------------------

