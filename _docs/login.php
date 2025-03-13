<?php
// ------------------------------------------------------------

// login.php
// Login page

// Include helper functions
require_once 'includes/helpers.php';

// Start session
startSession();

// Check if user is already logged in
if (isLoggedIn()) {
    // Redirect to projects page
    redirect(BASE_URL . '/projects.php');
}

// Set page title
$pageTitle = 'Login';

// Include login view
require_once ROOT_PATH . '/views/auth/login.php';
