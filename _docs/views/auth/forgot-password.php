<!--
views/auth/forgot-password.php
The forgot password page template
-->
<?php
// Set page title
$pageTitle = 'Forgot Password';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row">
    <div class="col-6" style="margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <h2>Forgot Password</h2>
            </div>
            <div class="card-body">
                <p>Enter your email address below and we'll send you a link to reset your password.</p>
                <form id="forgotPasswordForm" action="<?php echo BASE_URL; ?>/api/auth/reset-password.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Reset Password</button>
                    </div>
                    <p class="mt-3">
                        <a href="<?php echo BASE_URL; ?>/login.php">Back to Login</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/auth.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>