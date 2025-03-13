<?php
// views/errors/403.php
// 403 Forbidden error page

// Set status code
http_response_code(403);

// Include helper functions
require_once __DIR__ . '/../../includes/helpers.php';

// Set page title
$pageTitle = '403 Forbidden';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row">
    <div class="col-12 text-center">
        <h1>403 Forbidden</h1>
        <p>You don't have permission to access this resource.</p>
        <p><a href="<?php echo BASE_URL; ?>/projects.php" class="btn btn-primary">Back to Projects</a></p>
    </div>
</div>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>