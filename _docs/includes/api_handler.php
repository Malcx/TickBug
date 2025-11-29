<?php
/**
 * includes/api_handler.php
 * Centralized API request handling utilities
 * Reduces boilerplate code in API endpoint files
 */

/**
 * Initialize API request - handles common boilerplate
 *
 * @param array $options Configuration options
 *   - requireLogin: bool (default: true) - Require user to be logged in
 *   - requirePost: bool (default: true) - Require POST method
 *   - loginMessage: string - Custom message for login requirement
 *   - methodMessage: string - Custom message for method requirement
 *
 * @return array Returns ['userId' => int] on success, sends JSON response and exits on failure
 */
function initApiRequest($options = []) {
    // Set defaults
    $requireLogin = isset($options['requireLogin']) ? $options['requireLogin'] : true;
    $requirePost = isset($options['requirePost']) ? $options['requirePost'] : true;
    $loginMessage = isset($options['loginMessage']) ? $options['loginMessage'] : 'You must be logged in to perform this action.';
    $methodMessage = isset($options['methodMessage']) ? $options['methodMessage'] : 'Invalid request method.';

    // Start session
    startSession();

    // Check login requirement
    if ($requireLogin && !isLoggedIn()) {
        sendJsonResponse(['success' => false, 'message' => $loginMessage]);
    }

    // Check request method
    if ($requirePost && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        sendJsonResponse(['success' => false, 'message' => $methodMessage]);
    }

    return [
        'userId' => $requireLogin ? getCurrentUserId() : null
    ];
}

/**
 * Get and validate required POST parameters
 *
 * @param array $params Array of parameter definitions
 *   Each parameter: ['name' => string, 'type' => string, 'required' => bool, 'default' => mixed, 'message' => string]
 *   Types: 'int', 'string', 'bool', 'array', 'email'
 *
 * @return array Validated and typed parameters
 */
function getPostParams($params) {
    $result = [];

    foreach ($params as $param) {
        $name = $param['name'];
        $type = isset($param['type']) ? $param['type'] : 'string';
        $required = isset($param['required']) ? $param['required'] : false;
        $default = isset($param['default']) ? $param['default'] : null;
        $message = isset($param['message']) ? $param['message'] : ucfirst(str_replace('_', ' ', $name)) . ' is required.';

        // Check if parameter exists
        if (!isset($_POST[$name]) || (is_string($_POST[$name]) && trim($_POST[$name]) === '')) {
            if ($required) {
                sendJsonResponse(['success' => false, 'message' => $message]);
            }
            $result[$name] = $default;
            continue;
        }

        // Get and type the value
        $value = $_POST[$name];

        switch ($type) {
            case 'int':
                $result[$name] = (int)$value;
                break;
            case 'bool':
                $result[$name] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'array':
                $result[$name] = is_array($value) ? $value : [$value];
                break;
            case 'email':
                $value = trim($value);
                if ($required && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    sendJsonResponse(['success' => false, 'message' => 'Please enter a valid email address.']);
                }
                $result[$name] = $value;
                break;
            case 'string':
            default:
                $result[$name] = trim($value);
                break;
        }
    }

    return $result;
}

/**
 * Check user permission for a project action
 *
 * @param int $userId User ID
 * @param int $projectId Project ID
 * @param string $action Action to check (create_ticket, edit_ticket, delete_ticket, etc.)
 * @param string $errorMessage Custom error message
 *
 * @return string User's role in the project
 */
function checkProjectPermission($userId, $projectId, $action, $errorMessage = null) {
    $userRole = getUserProjectRole($userId, $projectId);

    if (!$userRole) {
        sendJsonResponse([
            'success' => false,
            'message' => $errorMessage ?: 'You do not have access to this project.'
        ]);
    }

    if (!canPerformAction($action, $userRole)) {
        sendJsonResponse([
            'success' => false,
            'message' => $errorMessage ?: 'You do not have permission to perform this action.'
        ]);
    }

    return $userRole;
}

/**
 * Check permission for ticket-related actions
 * Gets the ticket, validates it exists, and checks permission
 *
 * @param int $userId User ID
 * @param int $ticketId Ticket ID
 * @param string $action Action to check
 * @param string $errorMessage Custom error message
 *
 * @return array ['ticket' => array, 'projectId' => int, 'userRole' => string]
 */
function checkTicketPermission($userId, $ticketId, $action, $errorMessage = null) {
    $ticket = getTicket($ticketId);

    if (!$ticket) {
        sendJsonResponse(['success' => false, 'message' => 'Ticket not found.']);
    }

    $projectId = $ticket['project_id'];
    $userRole = checkProjectPermission($userId, $projectId, $action, $errorMessage);

    return [
        'ticket' => $ticket,
        'projectId' => $projectId,
        'userRole' => $userRole
    ];
}

/**
 * Check permission for deliverable-related actions
 * Gets the deliverable, validates it exists, and checks permission
 *
 * @param int $userId User ID
 * @param int $deliverableId Deliverable ID
 * @param string $action Action to check
 * @param string $errorMessage Custom error message
 *
 * @return array ['deliverable' => array, 'projectId' => int, 'userRole' => string]
 */
function checkDeliverablePermission($userId, $deliverableId, $action, $errorMessage = null) {
    $deliverable = getDeliverable($deliverableId);

    if (!$deliverable) {
        sendJsonResponse(['success' => false, 'message' => 'Deliverable not found.']);
    }

    $projectId = $deliverable['project_id'];
    $userRole = checkProjectPermission($userId, $projectId, $action, $errorMessage);

    return [
        'deliverable' => $deliverable,
        'projectId' => $projectId,
        'userRole' => $userRole
    ];
}

/**
 * Process file uploads from form data
 *
 * @param string $fieldName Name of the file input field (default: 'files')
 *
 * @return array Array of file data ready for processing
 */
function processFileUploads($fieldName = 'files') {
    $files = [];

    if (isset($_FILES[$fieldName]) && is_array($_FILES[$fieldName]['name'])) {
        for ($i = 0; $i < count($_FILES[$fieldName]['name']); $i++) {
            if ($_FILES[$fieldName]['error'][$i] === UPLOAD_ERR_OK) {
                $files[] = [
                    'name' => $_FILES[$fieldName]['name'][$i],
                    'type' => $_FILES[$fieldName]['type'][$i],
                    'tmp_name' => $_FILES[$fieldName]['tmp_name'][$i],
                    'error' => $_FILES[$fieldName]['error'][$i],
                    'size' => $_FILES[$fieldName]['size'][$i]
                ];
            }
        }
    }

    return $files;
}

/**
 * Handle non-AJAX requests (form submissions with redirects)
 *
 * @param array $result Result from the operation
 * @param string $successUrl URL to redirect to on success
 * @param string $errorUrl URL to redirect to on error
 * @param string $successMessage Flash message for success
 */
function handleFormResult($result, $successUrl, $errorUrl, $successMessage = null) {
    if (isAjaxRequest()) {
        sendJsonResponse($result);
    }

    if ($result['success']) {
        if ($successMessage) {
            setFlashMessage('success', $successMessage);
        } elseif (isset($result['message'])) {
            setFlashMessage('success', $result['message']);
        }
        redirect($successUrl);
    } else {
        setFlashMessage('error', $result['message']);
        redirect($errorUrl);
    }
}
