<?php
// index.php
// Main index page - redirects to login or projects

// Include helper functions
require_once 'includes/helpers.php';

// Start session
startSession();

// Check if user is logged in
if (isLoggedIn()) {
    // Redirect to projects page
    redirect(BASE_URL . '/projects.php');
} else {
    // Redirect to login page
    redirect(BASE_URL . '/login.php');
}