<!--
views/reports/project.php
Project status report template - COMPLETE VERSION
-->
<?php
// Set page title
$pageTitle = 'Project Status Report: ' . $report['project']['name'];

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row mb-3">
    <div class="col-8">
        <h1>Project Status Report</h1>
        <h2><?php echo htmlspecialchars($report['project']['name']); ?></h2>
    </div>
    <div class="col-4 text-right">
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>/reports.php" class="btn btn-secondary">Overview</a>
            <a href="<?php echo BASE_URL; ?>/reports.php?type=tickets&project_id=<?php echo $report['project']['project_id']; ?>" class="btn btn-secondary">Tickets by Status</a>
            <a href="<?php echo BASE_URL; ?>/reports.php?type=productivity&project_id=<?php echo $report['project']['project_id']; ?>" class="btn btn-secondary">Team Productivity</a>
        </div>
    </div>
</div>

<!-- Project stats -->
<div class="row mb-4">
    <div class="col-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Total Tickets</h5>
                <p class="card-text display-4"><?php echo $report['stats']['total_tickets']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Open Tickets</h5>
                <p class="card-text display-4"><?php echo $report['stats']['open_tickets']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Completed Tickets</h5>
                <p class="card-text display-4"><?php echo $report['stats']['completed_tickets']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Completion Rate</h5>
                <?php $completionRate = ($report['stats']['total_tickets'] > 0) ? round(($report['stats']['completed_tickets'] / $report['stats']['total_tickets']) * 100, 1) : 0; ?>
                <p class="card-text display-4"><?php echo $completionRate; ?>%</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Tickets by status -->
    <div class="col-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Tickets by Status</h3>
            </div>
            <div class="card-body">
                <?php if (empty($report['tickets_by_status'])): ?>
                    <div class="alert alert-info">No ticket data available.</div>
                <?php else: ?>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['tickets_by_status'] as $status => $count): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $status)); ?>"><?php echo $status; ?></span>
                                        </td>
                                        <td><?php echo $count; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Tickets by priority -->
    <div class="col-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Tickets by Priority</h3>
            </div>
            <div class="card-body">
                <?php if (empty($report['tickets_by_priority'])): ?>
                    <div class="alert alert-info">No ticket data available.</div>
                <?php else: ?>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="priorityChart"></canvas>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Priority</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['tickets_by_priority'] as $priority => $count): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-priority-<?php echo strtolower(str_replace(' ', '-', $priority)); ?>"><?php echo $priority; ?></span>
                                        </td>
                                        <td><?php echo $count; ?></td>
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

<div class="row">
    <!-- Tickets by assignee -->
    <div class="col-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Tickets by Assignee</h3>
            </div>
            <div class="card-body">
                <?php if (empty($report['tickets_by_assignee'])): ?>
                    <div class="alert alert-info">No assignment data available.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Assignee</th>
                                    <th>Count</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['tickets_by_assignee'] as $assignee => $count): 
                                    // Skip if this is a system-generated empty assignee key
                                    if (empty($assignee) && $assignee !== 'Unassigned') continue;
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($assignee); ?></td>
                                        <td><?php echo $count; ?></td>
                                        <td>
                                            <?php if ($assignee !== 'Unassigned'): 
                                                // Extract user ID from the project users by matching name
                                                $userId = null;
                                                $projectUsers = getProjectUsers($report['project']['project_id']);
                                                foreach ($projectUsers as $user) {
                                                    if ($user['first_name'] . ' ' . $user['last_name'] === $assignee) {
                                                        $userId = $user['user_id'];
                                                        break;
                                                    }
                                                }
                                                if ($userId):
                                            ?>
                                                <a href="<?php echo BASE_URL; ?>/reports.php?type=user&user_id=<?php echo $userId; ?>&project_id=<?php echo $report['project']['project_id']; ?>" class="btn btn-sm btn-primary">View User Report</a>
                                            <?php endif; endif; ?>
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
    
    <!-- Tickets by deliverable -->
    <div class="col-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Tickets by Deliverable</h3>
            </div>
            <div class="card-body">
                <?php if (empty($report['tickets_by_deliverable'])): ?>
                    <div class="alert alert-info">No deliverable data available.</div>
                <?php else: ?>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="deliverableChart"></canvas>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Deliverable</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['tickets_by_deliverable'] as $deliverable => $count): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($deliverable); ?></td>
                                        <td><?php echo $count; ?></td>
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
                <?php if (empty($report['activity'])): ?>
                    <div class="alert alert-info">No recent activity.</div>
                <?php else: ?>
                    <div class="activity-timeline">
                        <?php foreach ($report['activity'] as $activity): ?>
                            <div class="activity-item">
                                <div class="activity-time"><?php echo $activity['formatted_time']; ?></div>
                                <div class="activity-content">
                                    <p><?php echo $activity['description']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js for the charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4"></script>
<script>
$(document).ready(function() {
    // Status chart
    <?php if (!empty($report['tickets_by_status'])): ?>
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    var statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php echo "'" . implode("', '", array_keys($report['tickets_by_status'])) . "'"; ?>],
            datasets: [{
                data: [<?php echo implode(", ", array_values($report['tickets_by_status'])); ?>],
                backgroundColor: [
                    '#17a2b8', // New
                    '#ffc107', // Needs clarification
                    '#6f42c1', // Assigned
                    '#007bff', // In progress
                    '#fd7e14', // In review
                    '#28a745', // Complete
                    '#dc3545', // Rejected
                    '#6c757d'  // Ignored
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'right'
            }
        }
    });
    <?php endif; ?>
    
    // Priority chart
    <?php if (!empty($report['tickets_by_priority'])): ?>
    var priorityCtx = document.getElementById('priorityChart').getContext('2d');
    var priorityChart = new Chart(priorityCtx, {
        type: 'pie',
        data: {
            labels: [<?php echo "'" . implode("', '", array_keys($report['tickets_by_priority'])) . "'"; ?>],
            datasets: [{
                data: [<?php echo implode(", ", array_values($report['tickets_by_priority'])); ?>],
                backgroundColor: [
                    '#dc3545', // 1-Critical
                    '#fd7e14', // 1-Important
                    '#28a745', // 2-Nice to have
                    '#17a2b8', // 3-Feature Request
                    '#6c757d'  // 4-Nice to have
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            legend: {
                position: 'right'
            }
        }
    });
    <?php endif; ?>
    
    // Deliverable chart
    <?php if (!empty($report['tickets_by_deliverable'])): ?>
    var deliverableCtx = document.getElementById('deliverableChart').getContext('2d');
    var deliverableChart = new Chart(deliverableCtx, {
        type: 'bar',
        data: {
            labels: [<?php echo "'" . implode("', '", array_keys($report['tickets_by_deliverable'])) . "'"; ?>],
            datasets: [{
                label: 'Tickets',
                data: [<?php echo implode(", ", array_values($report['tickets_by_deliverable'])); ?>],
                backgroundColor: 'rgba(32, 30, 91, 0.7)',
                borderColor: 'rgba(32, 30, 91, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>