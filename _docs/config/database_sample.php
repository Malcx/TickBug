<?php
// config/database.php
// Database configuration

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tickbug');

// Create database connection
function getDbConnection() {
    static $conn;
    
    if ($conn === null) {
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Database connection failed: " . $conn->connect_error);
            }
            
            $conn->set_charset("utf8mb4");
        } catch (Exception $e) {
            if (DEBUG) {
                die($e->getMessage());
            } else {
                die("Database connection failed.");
            }
        }
    }
    
    return $conn;
}

// ------------------------------------------------------------

