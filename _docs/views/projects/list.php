<!--
views/projects/list.php
The projects list page template
-->
<?php
// Set page title
$pageTitle = 'My Projects';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';

// Get projects
require_once ROOT_PATH . '/includes/projects.php';
$userId = getCurrentUserId();
$projects = getUserProjects($userId);
?>

<div class="row mb-3">
    <div class="col-6">
        <h1>My Projects</h1>
    </div>
    <div class="col-6 text-right">
        <a href="<?php echo BASE_URL; ?>/projects.php?action=create" class="btn btn-primary">Create New Project</a>
    </div>
</div>

<?php if (empty($projects)): ?>
    <div class="alert alert-info">
        You don't have any projects yet. Create a new project to get started.
    </div>
<?php else: ?>
    <div class="row">
        <?php foreach ($projects as $project): ?>
            <div class="col-4 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo htmlspecialchars($project['name']); ?></h3>
                    </div>
                    <div class="card-body">
                        <p><?php echo htmlspecialchars($project['description']); ?></p>
                        
                        <?php if (isset($project['stats'])): ?>
                            <div class="mb-3">
                                <p><strong>Tickets:</strong> <?php echo $project['stats']['total_tickets']; ?></p>
                                <p><strong>Open:</strong> <?php echo $project['stats']['open_tickets']; ?></p>
                                <p><strong>Complete:</strong> <?php echo $project['stats']['completed_tickets']; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $project['project_id']; ?>" class="btn btn-primary">View Project</a>
                        <?php if ($project['role'] === 'Owner' || $project['role'] === 'Project Manager'): ?>
                            <a href="<?php echo BASE_URL; ?>/projects.php?action=edit&id=<?php echo $project['project_id']; ?>" class="btn btn-secondary">Edit</a>
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