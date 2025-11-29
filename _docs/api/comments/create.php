<?php
/**
 * api/comments/create.php
 * Create comment API endpoint
 */

require_once '../../includes/helpers.php';

// Initialize API request
$api = initApiRequest(['loginMessage' => 'You must be logged in to add a comment.']);
$userId = $api['userId'];

// Get and validate parameters
$params = getPostParams([
    ['name' => 'ticket_id', 'type' => 'int', 'required' => true, 'message' => 'Ticket ID is required.'],
    ['name' => 'description', 'type' => 'string', 'default' => ''],
    ['name' => 'url', 'type' => 'string', 'default' => '']
]);

// Check permission for ticket
checkTicketPermission($userId, $params['ticket_id'], 'add_comment', 'You do not have permission to comment on tickets.');

// Process file uploads
$files = processFileUploads('files');

// Add comment
$result = addComment($params['ticket_id'], $params['description'], $params['url'], $userId, $files);

sendJsonResponse($result);
