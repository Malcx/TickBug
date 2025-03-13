<!--
views/projects/archived.php
The archived projects list page template
-->
<?php
// Set page title
$pageTitle = 'Archived Projects';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';

// Get archived projects
require_once ROOT_PATH . '/includes/projects.php';
$userId = getCurrentUserId();
$archivedProjects = getUserArchivedProjects($userId);
?>

<div class="row mb-3">
    <div class="col-6">
        <h1>Archived Projects</h1>
    </div>
    <div class="col-6 text-right">
        <a href="<?php echo BASE_URL; ?>/projects.php" class="btn btn-secondary">Back to Active Projects</a>
    </div>
</div>

<?php if (empty($archivedProjects)): ?>
    <div class="alert alert-info">
        You don't have any archived projects.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($archivedProjects as $project): ?>
            <div class="col-4 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($project['name']); ?></h3>
                        <span class="badge badge-secondary">Archived</span>
                    </div>
                    <div class="card-body">
                        <p><?php echo htmlspecialchars($project['description']); ?></p>
                    </div>
                    <div class="card-footer">
                        <a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $project['project_id']; ?>" class="btn btn-primary">View Project</a>
                        <?php if ($project['role'] === 'Owner'): ?>
                            <a href="<?php echo BASE_URL; ?>/projects.php?action=unarchive&id=<?php echo $project['project_id']; ?>" class="btn btn-success">Unarchive</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script src="<?php echo BASE_URL; ?>/assets/js/projects.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>