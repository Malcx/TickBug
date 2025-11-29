<?php
/**
 * api/auth/login.php
 * Login API endpoint
 */

require_once '../../includes/helpers.php';

// Initialize API request (no login required, but POST method is)
$api = initApiRequest(['requireLogin' => false]);

// Get and validate parameters
$params = getPostParams([
    ['name' => 'email', 'type' => 'email', 'required' => true, 'message' => 'Email is required.'],
    ['name' => 'password', 'type' => 'string', 'required' => true, 'message' => 'Password is required.']
]);

// Attempt login
$result = loginUser($params['email'], $params['password']);

// Handle response (supports both AJAX and form submissions)
handleFormResult($result, BASE_URL . '/projects.php', BASE_URL . '/login.php', 'Login successful.');
