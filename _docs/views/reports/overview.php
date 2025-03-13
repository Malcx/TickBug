<!--
views/reports/overview.php
System overview report template
-->
<?php
// Set page title
$pageTitle = 'System Overview Report';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row mb-3">
    <div class="col-8">
        <h1>System Overview Report</h1>
    </div>
    <div class="col-4 text-right">
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>/reports.php?type=project<?php echo isset($_GET['project_id']) ? '&project_id=' . $_GET['project_id'] : ''; ?>" class="btn btn-secondary">Project Status</a>
            <a href="<?php echo BASE_URL; ?>/reports.php?type=tickets<?php echo isset($_GET['project_id']) ? '&project_id=' . $_GET['project_id'] : ''; ?>" class="btn btn-secondary">Tickets by Status</a>
            <a href="<?php echo BASE_URL; ?>/reports.php?type=productivity<?php echo isset($_GET['project_id']) ? '&project_id=' . $_GET['project_id'] : ''; ?>" class="btn btn-secondary">Team Productivity</a>
        </div>
    </div>
</div>

<!-- Key metrics -->
<div class="row mb-4">
    <div class="col-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <p class="card-text display-4"><?php echo $report['total_users']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Active Projects</h5>
                <p class="card-text display-4"><?php echo $report['active_projects']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Open Tickets</h5>
                <p class="card-text display-4"><?php echo $report['open_tickets']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Critical Tickets</h5>
                <p class="card-text display-4"><?php echo $report['critical_tickets']; ?></p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Most active projects -->
    <div class="col-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Most Active Projects</h3>
            </div>
            <div class="card-body">
                <?php if (empty($report['most_active_projects'])): ?>
                    <div class="alert alert-info">No project data available.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Project</th>
                                    <th>Tickets</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['most_active_projects'] as $project): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($project['name']); ?></td>
                                        <td><?php echo $project['ticket_count']; ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/reports.php?type=project&project_id=<?php echo $project['project_id']; ?>" class="btn btn-sm btn-primary">View Report</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Most active users -->
    <div class="col-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Most Active Users</h3>
            </div>
            <div class="card-body">
                <?php if (empty($report['most_active_users'])): ?>
                    <div class="alert alert-info">No user activity data available.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>User</th>
                                    <th>Activity Count</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['most_active_users'] as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                        <td><?php echo $user['activity_count']; ?></td>
                                        <td>
                                            <a href="<?php echo BASE_URL; ?>/reports.php?type=user&user_id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-primary">View Report</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Recent activity -->
<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3>Recent Activity</h3>
            </div>
            <div class="card-body">
                <?php if (empty($report['recent_activity'])): ?>
                    <div class="alert alert-info">No recent activity.</div>
                <?php else: ?>
                    <div class="activity-timeline">
                        <?php foreach ($report['recent_activity'] as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-time"><?php echo $activity['formatted_time']; ?></div>
                                <div class="activity-content">
                                    <p>
                                        <strong><?php echo htmlspecialchars($activity['project_name']); ?>:</strong>
                                        <?php echo $activity['description']; ?>
                                    </p>
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