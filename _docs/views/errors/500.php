<?php
// views/errors/500.php
// 500 Internal Server Error page

// Set status code
http_response_code(500);

// Include helper functions
require_once __DIR__ . '/../../includes/helpers.php';

// Set page title
$pageTitle = '500 Internal Server Error';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row">
    <div class="col-12 text-center">
        <h1>500 Internal Server Error</h1>
        <p>Something went wrong on our end. Please try again later.</p>
        <p><a href="<?php echo BASE_URL; ?>/projects.php" class="btn btn-primary">Back to Projects</a></p>
    </div>
</div>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>