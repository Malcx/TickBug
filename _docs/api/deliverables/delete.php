<?php
/**
 * api/deliverables/delete.php
 * Delete deliverable API endpoint
 */

require_once '../../includes/helpers.php';

// Initialize API request
$api = initApiRequest(['loginMessage' => 'You must be logged in to delete a deliverable.']);
$userId = $api['userId'];

// Get and validate parameters
$params = getPostParams([
    ['name' => 'deliverable_id', 'type' => 'int', 'required' => true, 'message' => 'Deliverable ID is required.']
]);

// Delete deliverable (deleteDeliverable handles permission checking internally)
$result = deleteDeliverable($params['deliverable_id'], $userId);

sendJsonResponse($result);
