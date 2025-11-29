<?php
/**
 * api/tickets/create.php
 * Create ticket API endpoint
 */

require_once '../../includes/helpers.php';

// Initialize API request - handles session, login check, and POST method check
$api = initApiRequest(['loginMessage' => 'You must be logged in to create a ticket.']);
$userId = $api['userId'];

// Get and validate parameters
$params = getPostParams([
    ['name' => 'deliverable_id', 'type' => 'int', 'required' => true, 'message' => 'Deliverable ID is required.'],
    ['name' => 'title', 'type' => 'string', 'required' => true, 'message' => 'Ticket title is required.'],
    ['name' => 'description', 'type' => 'string', 'default' => ''],
    ['name' => 'url', 'type' => 'string', 'default' => ''],
    ['name' => 'status_id', 'type' => 'int', 'default' => 1],
    ['name' => 'priority_id', 'type' => 'int', 'default' => 3],
    ['name' => 'assigned_to', 'type' => 'int', 'default' => null]
]);

// Check permission for deliverable
$permCheck = checkDeliverablePermission($userId, $params['deliverable_id'], 'create_ticket', 'You do not have permission to create tickets.');

// Process file uploads
$files = processFileUploads('files');

// Create ticket
$result = createTicket(
    $params['deliverable_id'],
    $params['title'],
    $params['description'],
    $params['url'],
    $params['status_id'],
    $params['priority_id'],
    $params['assigned_to'],
    $userId,
    $files
);

sendJsonResponse($result);
