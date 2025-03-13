<!--
views/reports/select_project.php
Project selection for reports template
-->
<?php
// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row mb-3">
    <div class="col-8">
        <h1>Select Project for Report</h1>
    </div>
    <div class="col-4 text-right">
        <a href="<?php echo BASE_URL; ?>/reports.php" class="btn btn-secondary">Back to Overview</a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h2>Select a Project</h2>
                <p>Please select a project to view the <?php 
                    if ($reportType == 'project') echo 'Project Status';
                    elseif ($reportType == 'tickets') echo 'Tickets by Status';
                    elseif ($reportType == 'productivity') echo 'Team Productivity';
                ?> report</p>
            </div>
            <div class="card-body">
                <?php if (empty($projects)): ?>
                    <div class="alert alert-info">
                        You don't have any projects yet. Create a new project to view reports.
                    </div>
                    <a href="<?php echo BASE_URL; ?>/projects.php?action=create" class="btn btn-primary">Create New Project</a>
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
                                        <a href="<?php echo BASE_URL; ?>/reports.php?type=<?php echo $reportType; ?>&project_id=<?php echo $project['project_id']; ?>" class="btn btn-primary">Select Project</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>