<?php
// includes/files.php
// File related functions

/**
 * Upload a file
 * 
 * @param array $file File data from $_FILES
 * @param int $userId User ID uploading the file
 * @return array Response with status and file data
 */
function uploadFile($file, $userId) {
    // Check if file is valid
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['success' => false, 'message' => 'Invalid file upload.'];
    }
    
    // Check file size
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File is too large. Maximum size is ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB.'];
    }
    
    // Check file type using finfo (more reliable than deprecated mime_content_type)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $filetype = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!$filetype || !in_array($filetype, ALLOWED_FILE_TYPES)) {
        return ['success' => false, 'message' => 'File type not allowed.'];
    }

    // Validate file extension as additional security measure
    $allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'csv', 'zip'];
    $filename = $file['name'];
    $fileExtension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedExtensions)) {
        return ['success' => false, 'message' => 'File extension not allowed.'];
    }

    // Create uploads directory if it doesn't exist
    if (!file_exists(UPLOADS_PATH)) {
        mkdir(UPLOADS_PATH, 0755, true);
    }
    
    // Generate unique filename
    $filename = $file['name'];
    $fileExtension = pathinfo($filename, PATHINFO_EXTENSION);
    $baseFilename = pathinfo($filename, PATHINFO_FILENAME);
    $safeFilename = preg_replace("/[^a-zA-Z0-9_-]/", "_", $baseFilename);
    $uniqueFilename = $safeFilename . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $filepath = UPLOADS_PATH . '/' . $uniqueFilename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => false, 'message' => 'Failed to save uploaded file.'];
    }
    
    // Store file information in database
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        INSERT INTO files (filename, filepath, filesize, filetype, uploaded_by)
        VALUES (?, ?, ?, ?, ?)
    ");
    $relativeFilepath = str_replace(ROOT_PATH, '', $filepath);
    $stmt->bind_param("ssisi", $filename, $relativeFilepath, $file['size'], $filetype, $userId);
    
    if ($stmt->execute()) {
        $fileId = $conn->insert_id;
        
        return [
            'success' => true,
            'message' => 'File uploaded successfully.',
            'file_id' => $fileId,
            'filename' => $filename,
            'filepath' => $relativeFilepath,
            'filesize' => $file['size'],
            'filetype' => $filetype
        ];
    } else {
        // Delete the file if database insert fails
        unlink($filepath);
        
        return ['success' => false, 'message' => 'Failed to save file information: ' . $conn->error];
    }
}

/**
 * Get a file by ID
 * 
 * @param int $fileId File ID
 * @return array|false File data or false if not found
 */
function getFile($fileId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT f.*, u.first_name, u.last_name
        FROM files f
        JOIN users u ON f.uploaded_by = u.user_id
        WHERE f.file_id = ?
    ");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    return $result->fetch_assoc();
}

/**
 * Delete a file
 * 
 * @param int $fileId File ID
 * @param int $userId User ID making the deletion
 * @return array Response with status and message
 */
function deleteFile($fileId, $userId) {
    $conn = getDbConnection();
    
    // Get file information
    $file = getFile($fileId);
    
    if (!$file) {
        return ['success' => false, 'message' => 'File not found.'];
    }
    
    // Determine if file is attached to a ticket or comment
    $stmt = $conn->prepare("
        SELECT tf.ticket_id, d.project_id
        FROM ticket_files tf
        JOIN tickets t ON tf.ticket_id = t.ticket_id
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        WHERE tf.file_id = ?
    ");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $ticketResult = $stmt->get_result();
    
    $stmt = $conn->prepare("
        SELECT c.ticket_id, d.project_id
        FROM comment_files cf
        JOIN comments c ON cf.comment_id = c.comment_id
        JOIN tickets t ON c.ticket_id = t.ticket_id
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        WHERE cf.file_id = ?
    ");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $commentResult = $stmt->get_result();
    
    // Determine project ID and check permissions
    $projectId = null;
    $itemType = null;
    $itemId = null;
    
    if ($ticketResult->num_rows > 0) {
        $row = $ticketResult->fetch_assoc();
        $projectId = $row['project_id'];
        $itemType = 'ticket';
        $itemId = $row['ticket_id'];
    } elseif ($commentResult->num_rows > 0) {
        $row = $commentResult->fetch_assoc();
        $projectId = $row['project_id'];
        $itemType = 'comment';
        $itemId = $row['ticket_id']; // Store ticket ID for logging
    } else {
        return ['success' => false, 'message' => 'File is not attached to any item.'];
    }
    
    // Check if user has permission
    $userRole = getUserProjectRole($userId, $projectId);
    
    if (!$userRole || $userRole === 'Viewer') {
        return ['success' => false, 'message' => 'You do not have permission to delete this file.'];
    }
    
    // Only file uploader, ticket owner, or project admin can delete
    if ($file['uploaded_by'] !== $userId && 
        $userRole !== 'Owner' && $userRole !== 'Project Manager') {
        return ['success' => false, 'message' => 'You do not have permission to delete this file.'];
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Delete ticket_files or comment_files references
        $stmt = $conn->prepare("DELETE FROM ticket_files WHERE file_id = ?");
        $stmt->bind_param("i", $fileId);
        $stmt->execute();
        
        $stmt = $conn->prepare("DELETE FROM comment_files WHERE file_id = ?");
        $stmt->bind_param("i", $fileId);
        $stmt->execute();
        
        // Delete file record
        $stmt = $conn->prepare("DELETE FROM files WHERE file_id = ?");
        $stmt->bind_param("i", $fileId);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to delete file record: " . $conn->error);
        }
        
        // Delete physical file
        $filepath = ROOT_PATH . $file['filepath'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }
        
        // Log activity
        if (LOG_ACTIONS) {
            logActivity($userId, $projectId, 'file', $fileId, 'deleted', [
                'filename' => $file['filename'],
                'item_type' => $itemType,
                'item_id' => $itemId
            ]);
        }
        
        // Commit transaction
        $conn->commit();
        
        return ['success' => true, 'message' => 'File deleted successfully.'];
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Check if a user can access a file
 * 
 * @param int $fileId File ID
 * @param int $userId User ID
 * @return bool True if user can access file, false otherwise
 */
function canAccessFile($fileId, $userId) {
    $conn = getDbConnection();
    
    // Check if file is attached to a ticket
    $stmt = $conn->prepare("
        SELECT d.project_id
        FROM ticket_files tf
        JOIN tickets t ON tf.ticket_id = t.ticket_id
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        WHERE tf.file_id = ?
    ");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $ticketResult = $stmt->get_result();
    
    if ($ticketResult->num_rows > 0) {
        $row = $ticketResult->fetch_assoc();
        $projectId = $row['project_id'];
        
        // Check if user has access to this project
        $userRole = getUserProjectRole($userId, $projectId);
        return ($userRole !== false);
    }
    
    // Check if file is attached to a comment
    $stmt = $conn->prepare("
        SELECT d.project_id
        FROM comment_files cf
        JOIN comments c ON cf.comment_id = c.comment_id
        JOIN tickets t ON c.ticket_id = t.ticket_id
        JOIN deliverables d ON t.deliverable_id = d.deliverable_id
        WHERE cf.file_id = ?
    ");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $commentResult = $stmt->get_result();
    
    if ($commentResult->num_rows > 0) {
        $row = $commentResult->fetch_assoc();
        $projectId = $row['project_id'];
        
        // Check if user has access to this project
        $userRole = getUserProjectRole($userId, $projectId);
        return ($userRole !== false);
    }
    
    // If not attached to ticket or comment, only the uploader can access
    $stmt = $conn->prepare("SELECT uploaded_by FROM files WHERE file_id = ?");
    $stmt->bind_param("i", $fileId);
    $stmt->execute();
    $fileResult = $stmt->get_result();
    
    if ($fileResult->num_rows > 0) {
        $row = $fileResult->fetch_assoc();
        return ((int)$row['uploaded_by'] === (int)$userId);
    }

    return false;
}