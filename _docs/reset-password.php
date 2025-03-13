<?php
// ------------------------------------------------------------

// reset-password.php
// Reset password page

// Include helper functions
require_once 'includes/helpers.php';

// Start session
startSession();

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to projects page
    redirect(BASE_URL . '/projects.php');
}

// Check if token is provided
if (!isset($_GET['token']) || empty($_GET['token'])) {
    // Redirect to forgot password page
    setFlashMessage('error', 'Invalid password reset link.');
    redirect(BASE_URL . '/forgot-password.php');
}

// Set page title
$pageTitle = 'Reset Password';

// Include reset password view
require_once ROOT_PATH . '/views/auth/reset-password.php';
