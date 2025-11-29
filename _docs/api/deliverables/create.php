<?php
/**
 * api/deliverables/create.php
 * Create deliverable API endpoint
 */

require_once '../../includes/helpers.php';

// Initialize API request
$api = initApiRequest(['loginMessage' => 'You must be logged in to create a deliverable.']);
$userId = $api['userId'];

// Get and validate parameters
$params = getPostParams([
    ['name' => 'project_id', 'type' => 'int', 'required' => true, 'message' => 'Project ID is required.'],
    ['name' => 'name', 'type' => 'string', 'required' => true, 'message' => 'Deliverable name is required.'],
    ['name' => 'description', 'type' => 'string', 'default' => '']
]);

// Check permission
checkProjectPermission($userId, $params['project_id'], 'create_deliverable', 'You do not have permission to create deliverables.');

// Create deliverable
$result = createDeliverable($params['project_id'], $params['name'], $params['description'], $userId);

sendJsonResponse($result);
