<!--
views/auth/login.php
The login page template
-->
<?php
// Set page title
$pageTitle = 'Login';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row">
    <div class="col-6" style="margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <h2>Login</h2>
            </div>
            <div class="card-body">
                <form id="loginForm" action="<?php echo BASE_URL; ?>/api/auth/login.php" method="POST">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                    <p class="mt-3">
                        <a href="<?php echo BASE_URL; ?>/forgot-password.php">Forgot Password?</a>
                    </p>
                    <p>
                        Don't have an account? <a href="<?php echo BASE_URL; ?>/register.php">Register</a>
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