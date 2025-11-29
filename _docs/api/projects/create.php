<?php
/**
 * api/projects/create.php
 * Create project API endpoint
 */

require_once '../../includes/helpers.php';

// Initialize API request
$api = initApiRequest(['loginMessage' => 'You must be logged in to create a project.']);
$userId = $api['userId'];

// Get and validate parameters
$params = getPostParams([
    ['name' => 'name', 'type' => 'string', 'required' => true, 'message' => 'Project name is required.'],
    ['name' => 'description', 'type' => 'string', 'default' => ''],
    ['name' => 'theme_color', 'type' => 'string', 'default' => '#20205E']
]);

// Create project
$result = createProject($params['name'], $params['description'], $params['theme_color'], $userId);

sendJsonResponse($result);
