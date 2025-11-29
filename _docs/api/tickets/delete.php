<?php
/**
 * api/tickets/delete.php
 * Delete ticket API endpoint
 */

require_once '../../includes/helpers.php';

// Initialize API request
$api = initApiRequest(['loginMessage' => 'You must be logged in to delete a ticket.']);
$userId = $api['userId'];

// Get and validate parameters
$params = getPostParams([
    ['name' => 'ticket_id', 'type' => 'int', 'required' => true, 'message' => 'Ticket ID is required.']
]);

// Delete ticket (deleteTicket function handles permission checking internally)
$result = deleteTicket($params['ticket_id'], $userId);

sendJsonResponse($result);
