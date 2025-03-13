<!--
views/projects/create.php
The create project page template
-->
<?php
// Set page title
$pageTitle = 'Create Project';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row">
    <div class="col-8" style="margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <h2>Create New Project</h2>
            </div>
            <div class="card-body">
                <form id="createProjectForm" action="<?php echo BASE_URL; ?>/api/projects/create.php" method="POST">
                    <div class="form-group">
                        <label for="name">Project Name</label>
                        <input type="text" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5"></textarea>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Create Project</button>
                        <a href="<?php echo BASE_URL; ?>/projects.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="<?php echo BASE_URL; ?>/assets/js/projects.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>