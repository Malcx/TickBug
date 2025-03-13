<?php
// config/config.php
// Main configuration file

// Debug mode - set to false in production
define('DEBUG', true);

// Application paths
define('SITE_NAME', 'TickBug');
define('BASE_URL', 'http://localhost/bug-tracker');
define('ROOT_PATH', dirname(__DIR__));
define('UPLOADS_PATH', ROOT_PATH . '/uploads');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB

// Set timezone
date_default_timezone_set('UTC');

// Session settings
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS

// Error reporting
if (DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Include database configuration
require_once 'database.php';

// Include mailgun configuration
require_once 'mailgun.php';

// Auth settings
define('PASSWORD_COST', 12); // bcrypt cost

// File upload types
define('ALLOWED_FILE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'application/pdf',
    'text/plain',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
]);

// Log settings
define('LOG_ACTIONS', true);

// ------------------------------------------------------------

