<!--
views/auth/reset-password.php
The reset password page template
-->
<?php
// Set page title
$pageTitle = 'Reset Password';

// Check if token is valid
$token = isset($_GET['token']) ? $_GET['token'] : '';
$tokenValid = false;

if (!empty($token)) {
    require_once ROOT_PATH . '/includes/auth.php';
    $tokenInfo = verifyResetToken($token);
    $tokenValid = $tokenInfo['valid'];
}

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row">
    <div class="col-6" style="margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <h2>Reset Password</h2>
            </div>
            <div class="card-body">
                <?php if (!$tokenValid): ?>
                    <div class="alert alert-danger">
                        Invalid or expired reset token. Please request a new password reset.
                    </div>
                    <p class="mt-3">
                        <a href="<?php echo BASE_URL; ?>/forgot-password.php" class="btn btn-primary">Request New Reset</a>
                    </p>
                <?php else: ?>
                    <form id="resetPasswordForm" action="<?php echo BASE_URL; ?>/api/auth/reset-password.php" method="POST">
                        <input type="hidden" name="token" value="<?php echo $token; ?>">
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="confirmPassword">Confirm New Password</label>
                            <input type="password" id="confirmPassword" name="confirm_password" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Reset Password</button>
                        </div>
                        <p class="mt-3">
                            <a href="<?php echo BASE_URL; ?>/login.php">Back to Login</a>
                        </p>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/auth.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>