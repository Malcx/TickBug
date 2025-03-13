<?php
// includes/auth.php
// User authentication functions

// Start session if not already started
function startSession() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Register a new user
 * 
 * @param string $email User email
 * @param string $password User password
 * @param string $firstName User first name
 * @param string $lastName User last name
 * @return array Response with status and message
 */
function registerUser($email, $password, $firstName, $lastName) {
    $conn = getDbConnection();
    
    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Invalid email address.'];
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Email already in use.'];
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users (email, password, first_name, last_name) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $email, $hashedPassword, $firstName, $lastName);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        return ['success' => true, 'message' => 'Registration successful.', 'user_id' => $userId];
    } else {
        return ['success' => false, 'message' => 'Registration failed: ' . $conn->error];
    }
}

/**
 * Login a user
 * 
 * @param string $email User email
 * @param string $password User password
 * @return array Response with status and message
 */
function loginUser($email, $password) {
    $conn = getDbConnection();
    startSession();
    
    $stmt = $conn->prepare("SELECT user_id, email, password, first_name, last_name FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
    
    $user = $result->fetch_assoc();
    
    if (password_verify($password, $user['password'])) {
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name'] = $user['last_name'];
        $_SESSION['logged_in'] = true;
        
        // Update password if needed (if using an older algorithm)
        if (password_needs_rehash($user['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST])) {
            $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
            $updateStmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $updateStmt->bind_param("si", $newHash, $user['user_id']);
            $updateStmt->execute();
        }
        
        return ['success' => true, 'message' => 'Login successful.', 'user' => [
            'user_id' => $user['user_id'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name']
        ]];
    } else {
        return ['success' => false, 'message' => 'Invalid email or password.'];
    }
}

/**
 * Check if user is logged in
 * 
 * @return bool True if logged in, false otherwise
 */
function isLoggedIn() {
    startSession();
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Log out user
 * 
 * @return bool Always returns true
 */
function logoutUser() {
    startSession();
    
    // Unset all session variables
    $_SESSION = [];
    
    // Destroy the session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    session_destroy();
    
    return true;
}

/**
 * Get current user ID
 * 
 * @return int|null User ID if logged in, null otherwise
 */
function getCurrentUserId() {
    startSession();
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

/**
 * Get current user data
 * 
 * @return array|null User data if logged in, null otherwise
 */
function getCurrentUser() {
    startSession();
    
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'first_name' => $_SESSION['first_name'],
        'last_name' => $_SESSION['last_name']
    ];
}

/**
 * Create password reset token
 * 
 * @param string $email User email
 * @return array Response with status and message
 */
function createPasswordResetToken($email) {
    $conn = getDbConnection();
    
    // Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Don't reveal that the email doesn't exist
        return ['success' => true, 'message' => 'If your email exists in our system, you will receive a password reset link.'];
    }
    
    $user = $result->fetch_assoc();
    $userId = $user['user_id'];
    
    // Generate token
    $token = bin2hex(random_bytes(32));
    $tokenExpiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
    
    // Store token in database
    $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expiry = ? WHERE user_id = ?");
    $stmt->bind_param("ssi", $token, $tokenExpiry, $userId);
    $stmt->execute();
    
    // Send reset email
    $resetUrl = BASE_URL . '/reset-password.php?token=' . $token;
    $emailHtml = '
        <h2>Password Reset</h2>
        <p>You requested a password reset for your Bug Tracker account.</p>
        <p>Please click the link below to reset your password:</p>
        <p><a href="' . $resetUrl . '">' . $resetUrl . '</a></p>
        <p>This link will expire in 1 hour.</p>
        <p>If you did not request this reset, you can ignore this email.</p>
    ';
    
    $emailText = "Password Reset\n\n" .
                "You requested a password reset for your Bug Tracker account.\n\n" .
                "Please visit the following link to reset your password:\n" .
                $resetUrl . "\n\n" .
                "This link will expire in 1 hour.\n\n" .
                "If you did not request this reset, you can ignore this email.";
    
    sendEmail($email, 'Password Reset', $emailHtml, $emailText);
    
    return ['success' => true, 'message' => 'If your email exists in our system, you will receive a password reset link.'];
}

/**
 * Verify password reset token
 * 
 * @param string $token Reset token
 * @return array Response with status, message, and user ID if valid
 */
function verifyResetToken($token) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT user_id, email FROM users WHERE reset_token = ? AND reset_token_expiry > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['valid' => false, 'message' => 'Invalid or expired token.'];
    }
    
    $user = $result->fetch_assoc();
    
    return ['valid' => true, 'user_id' => $user['user_id'], 'email' => $user['email']];
}

/**
 * Reset user password
 * 
 * @param string $token Reset token
 * @param string $newPassword New password
 * @return array Response with status and message
 */
function resetPassword($token, $newPassword) {
    $conn = getDbConnection();
    
    // Verify token
    $tokenVerification = verifyResetToken($token);
    
    if (!$tokenVerification['valid']) {
        return ['success' => false, 'message' => $tokenVerification['message']];
    }
    
    $userId = $tokenVerification['user_id'];
    
    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
    
    // Update password and clear token
    $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE user_id = ?");
    $stmt->bind_param("si", $hashedPassword, $userId);
    
    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Password reset successful. You can now log in with your new password.'];
    } else {
        return ['success' => false, 'message' => 'Password reset failed: ' . $conn->error];
    }
}

/**
 * Update user profile
 * 
 * @param int $userId User ID
 * @param array $data Profile data to update
 * @return array Response with status and message
 */
function updateUserProfile($userId, $data) {
    $conn = getDbConnection();
    
    // Check if the user exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'User not found.'];
    }
    
    // Build update query
    $updates = [];
    $params = [];
    $types = "";
    
    if (isset($data['first_name'])) {
        $updates[] = "first_name = ?";
        $params[] = $data['first_name'];
        $types .= "s";
    }
    
    if (isset($data['last_name'])) {
        $updates[] = "last_name = ?";
        $params[] = $data['last_name'];
        $types .= "s";
    }
    
    if (isset($data['email'])) {
        // Validate email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address.'];
        }
        
        // Check if email is already in use by another user
        $emailStmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id != ?");
        $emailStmt->bind_param("si", $data['email'], $userId);
        $emailStmt->execute();
        $emailResult = $emailStmt->get_result();
        
        if ($emailResult->num_rows > 0) {
            return ['success' => false, 'message' => 'Email already in use by another user.'];
        }
        
        $updates[] = "email = ?";
        $params[] = $data['email'];
        $types .= "s";
    }
    
    if (isset($data['password']) && !empty($data['password'])) {
        $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        $updates[] = "password = ?";
        $params[] = $hashedPassword;
        $types .= "s";
    }
    
    if (empty($updates)) {
        return ['success' => false, 'message' => 'No data to update.'];
    }
    
    // Add user_id to parameters
    $params[] = $userId;
    $types .= "i";
    
    // Execute update
    $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    
    // Dynamically bind parameters
    $bindParams = array_merge([$types], $params);
    $stmt->bind_param(...$bindParams);
    
    if ($stmt->execute()) {
        // Update session if current user
        if (getCurrentUserId() === $userId) {
            startSession();
            
            if (isset($data['first_name'])) {
                $_SESSION['first_name'] = $data['first_name'];
            }
            
            if (isset($data['last_name'])) {
                $_SESSION['last_name'] = $data['last_name'];
            }
            
            if (isset($data['email'])) {
                $_SESSION['email'] = $data['email'];
            }
        }
        
        return ['success' => true, 'message' => 'Profile updated successfully.'];
    } else {
        return ['success' => false, 'message' => 'Profile update failed: ' . $conn->error];
    }
}