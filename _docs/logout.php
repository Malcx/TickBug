<?php
// ------------------------------------------------------------

// logout.php
// Logout page

// Include helper functions
require_once 'includes/helpers.php';

// Perform logout
$result = logoutUser();

// Redirect to login page
setFlashMessage('success', 'You have been logged out successfully.');
redirect(BASE_URL . '/login.php');
