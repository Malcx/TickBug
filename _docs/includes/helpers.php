<?php
// includes/helpers.php
// Helper functions and includes

// Include configuration
require_once __DIR__ . '/../config/config.php';

// Include core modules
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/projects.php';
require_once __DIR__ . '/deliverables.php';
require_once __DIR__ . '/tickets.php';
require_once __DIR__ . '/comments.php';
require_once __DIR__ . '/files.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/activity.php';
require_once __DIR__ . '/reports.php';

/**
 * Sanitize output for HTML
 * 
 * @param string $input Input string
 * @return string Sanitized string
 */
function sanitizeOutput($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Format date for display
 * 
 * @param string $date Date string
 * @param string $format Format string (default: 'M j, Y g:i A')
 * @return string Formatted date
 */
function formatDate($date, $format = 'M j, Y g:i A') {
    return date($format, strtotime($date));
}

/**
 * Get file extension class for icon display
 * 
 * @param string $filetype MIME type
 * @return string CSS class for icon
 */
function getFileIconClass($filetype) {
    $imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
    $documentTypes = [
        'application/pdf', 
        'application/msword', 
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
    ];
    $spreadsheetTypes = [
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    if (in_array($filetype, $imageTypes)) {
        return 'file-image';
    } elseif (in_array($filetype, $documentTypes)) {
        return 'file-document';
    } elseif (in_array($filetype, $spreadsheetTypes)) {
        return 'file-spreadsheet';
    } else {
        return 'file-generic';
    }
}

/**
 * Format file size for display
 * 
 * @param int $bytes File size in bytes
 * @return string Formatted file size
 */
function formatFileSize($bytes) {
    if ($bytes < 1024) {
        return $bytes . ' B';
    } elseif ($bytes < 1048576) {
        return round($bytes / 1024, 1) . ' KB';
    } elseif ($bytes < 1073741824) {
        return round($bytes / 1048576, 1) . ' MB';
    } else {
        return round($bytes / 1073741824, 1) . ' GB';
    }
}

/**
 * Check if user can perform an action
 * 
 * @param string $action Action to check
 * @param string $userRole User's role in the project
 * @return bool True if user can perform action, false otherwise
 */
function canPerformAction($action, $userRole) {
    switch ($action) {
        case 'view_project':
            // All roles can view projects
            return true;
            
        case 'edit_project':
        case 'add_user':
        case 'remove_user':
            // Only Owner and Project Manager can edit projects and manage users
            return ($userRole === 'Owner' || $userRole === 'Project Manager');
            
        case 'archive_project':
            // Only Owner can archive projects
            return ($userRole === 'Owner');
            
        case 'create_deliverable':
        case 'edit_deliverable':
        case 'reorder_deliverable':
            // All roles except Viewer and Tester can manage deliverables
            return ($userRole !== 'Viewer' && $userRole !== 'Tester');
            
        case 'delete_deliverable':
            // Only Owner and Project Manager can delete deliverables
            return ($userRole === 'Owner' || $userRole === 'Project Manager');
            
        case 'create_ticket':
            // All roles except Viewer can create tickets
            return ($userRole !== 'Viewer');
            
        case 'edit_ticket':
        case 'assign_ticket':
        case 'change_status':
        case 'reorder_ticket':
            // All roles except Viewer and Tester can manage tickets
            return ($userRole !== 'Viewer' && $userRole !== 'Tester');
            
        case 'delete_ticket':
            // Only Owner and Project Manager can delete tickets
            return ($userRole === 'Owner' || $userRole === 'Project Manager');
            
        case 'add_comment':
            // All roles except Viewer can comment
            return ($userRole !== 'Viewer');
            
        case 'view_reports':
            // Owner, Project Manager, and Reviewer can view reports
            return ($userRole === 'Owner' || $userRole === 'Project Manager' || $userRole === 'Reviewer');
            
        default:
            return false;
    }
}

/**
 * Set flash message
 * 
 * @param string $type Message type (success, error)
 * @param string $message Message text
 */
function setFlashMessage($type, $message) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if ($type === 'success') {
        $_SESSION['success_message'] = $message;
    } else {
        $_SESSION['error_message'] = $message;
    }
}

/**
 * Check if a file is an image
 * 
 * @param string $filetype MIME type
 * @return bool True if file is an image, false otherwise
 */
function isImage($filetype) {
    $imageTypes = ['image/jpeg', 'image/png', 'image/gif'];
    return in_array($filetype, $imageTypes);
}

/**
 * Redirect to a URL
 * 
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Check if request is AJAX
 * 
 * @return bool True if request is AJAX, false otherwise
 */
function isAjaxRequest() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

/**
 * Send JSON response
 * 
 * @param array $data Response data
 */
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/**
 * Generate pagination links
 * 
 * @param int $currentPage Current page number
 * @param int $totalPages Total number of pages
 * @param string $urlPattern URL pattern with %d placeholder for page number
 * @return string HTML for pagination links
 */
function generatePagination($currentPage, $totalPages, $urlPattern) {
    if ($totalPages <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    // Previous page link
    if ($currentPage > 1) {
        $html .= '<a href="' . sprintf($urlPattern, $currentPage - 1) . '" class="prev">&laquo; Previous</a>';
    } else {
        $html .= '<span class="prev disabled">&laquo; Previous</span>';
    }
    
    // Page links
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);
    
    if ($startPage > 1) {
        $html .= '<a href="' . sprintf($urlPattern, 1) . '">1</a>';
        if ($startPage > 2) {
            $html .= '<span class="ellipsis">...</span>';
        }
    }
    
    for ($i = $startPage; $i <= $endPage; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="current">' . $i . '</span>';
        } else {
            $html .= '<a href="' . sprintf($urlPattern, $i) . '">' . $i . '</a>';
        }
    }
    
    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<span class="ellipsis">...</span>';
        }
        $html .= '<a href="' . sprintf($urlPattern, $totalPages) . '">' . $totalPages . '</a>';
    }
    
    // Next page link
    if ($currentPage < $totalPages) {
        $html .= '<a href="' . sprintf($urlPattern, $currentPage + 1) . '" class="next">Next &raquo;</a>';
    } else {
        $html .= '<span class="next disabled">Next &raquo;</span>';
    }
    
    $html .= '</div>';
    
    return $html;
}



// Function to generate theme colors from a base color
function generateThemeColors($hexColor) {
    // Convert hex to RGB
    $hex = ltrim($hexColor, '#');
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    
    // Convert RGB to HSL
    $r_percent = $r / 255;
    $g_percent = $g / 255;
    $b_percent = $b / 255;
    
    $max_color = max($r_percent, $g_percent, $b_percent);
    $min_color = min($r_percent, $g_percent, $b_percent);
    
    $l = ($max_color + $min_color) / 2;
    $d = $max_color - $min_color;
    
    if($d == 0) {
        $h = $s = 0; // achromatic
    } else {
        $s = $d / (1 - abs(2 * $l - 1));
        
        switch($max_color) {
            case $r_percent:
                $h = 60 * fmod((($g_percent - $b_percent) / $d), 6);
                if ($b_percent > $g_percent) {
                    $h += 360;
                }
                break;
            case $g_percent:
                $h = 60 * (($b_percent - $r_percent) / $d + 2);
                break;
            case $b_percent:
                $h = 60 * (($r_percent - $g_percent) / $d + 4);
                break;
        }
    }
    
    // Create darker shade (reduce lightness)
    $darker_l = max($l - 0.15, 0.1);

    // Create darkest shade (reduce lightness)
    $darker_l2 = max($l - 0.25, 0.2);
    
    // Create lighter shade
    $lighter_l = min(0.85, $l + 0.4); // much lighter
    $lighter_s = max(0.1, $s - 0.2);  // less saturated

    // Create lightest shade
    $lighter_l2 = min(0.95, $l + 0.6); // much lighter
    $lighter_s2 = max(0.2, $s - 0.4);  // less saturated
    
    // Convert back to hex
    $dark = $hexColor;
    $darker = hslToHex($h, $s, $darker_l);
    $light = hslToHex($h, $lighter_s, $lighter_l);

    $darkest = hslToHex($h, $s, $darker_l2);
    $lightest = hslToHex($h, $lighter_s2, $lighter_l2);
    
    return [
        'dark' => $dark,
        'darker' => $darker,
        'darkest' => $darkest,
        'light' => $light,
        'lightest' => $lightest,
    ];
}

// Function to convert HSL to hex
function hslToHex($h, $s, $l) {
    $h /= 60;
    $s = min(max($s, 0), 1);
    $l = min(max($l, 0), 1);
    
    $c = (1 - abs(2 * $l - 1)) * $s;
    $x = $c * (1 - abs(fmod($h, 2) - 1));
    $m = $l - $c/2;
    
    if ($h < 1) {
        $r = $c;
        $g = $x;
        $b = 0;
    } elseif ($h < 2) {
        $r = $x;
        $g = $c;
        $b = 0;
    } elseif ($h < 3) {
        $r = 0;
        $g = $c;
        $b = $x;
    } elseif ($h < 4) {
        $r = 0;
        $g = $x;
        $b = $c;
    } elseif ($h < 5) {
        $r = $x;
        $g = 0;
        $b = $c;
    } else {
        $r = $c;
        $g = 0;
        $b = $x;
    }
    
    $r = sprintf('%02x', max(0, min(255, round(($r + $m) * 255))));
    $g = sprintf('%02x', max(0, min(255, round(($g + $m) * 255))));
    $b = sprintf('%02x', max(0, min(255, round(($b + $m) * 255))));
    
    return "#$r$g$b";
}