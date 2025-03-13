<?php

// ------------------------------------------------------------

// profile.php
// User profile page

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

// Get current user data
$userId = getCurrentUserId();
$user = getCurrentUser();

// Get user statistics
$stats = getUserStatistics($userId);

// Get notification settings
$notificationSettings = getUserNotificationSettings($userId);

// Set page title
$pageTitle = 'My Profile';

// Include profile view
require_once ROOT_PATH . '/views/users/profile.php';
