<?php
// views/errors/404.php
// 404 Not Found error page

// Set status code
http_response_code(404);

// Include helper functions
require_once __DIR__ . '/../../includes/helpers.php';

// Set page title
$pageTitle = '404 Not Found';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row">
    <div class="col-12 text-center">
        <h1>404 Not Found</h1>
        <p>The requested resource could not be found.</p>
        <p><a href="<?php echo BASE_URL; ?>/projects.php" class="btn btn-primary">Back to Projects</a></p>
    </div>
</div>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>