<?php
// views/reports/tickets.php
// Tickets by status report template

// Set page title
$pageTitle = isset($report['project']) ? 'Ticket Status Report: ' . $report['project']['name'] : 'All Tickets Status Report';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row mb-3">
    <div class="col-8">
        <h1>Tickets by Status Report</h1>
        <?php if (isset($report['project'])): ?>
            <h2><?php echo htmlspecialchars($report['project']['name']); ?></h2>
        <?php endif; ?>
    </div>
    <div class="col-4 text-right">
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>/reports.php" class="btn btn-secondary">Overview</a>
            <?php if (isset($report['project'])): ?>
                <a href="<?php echo BASE_URL; ?>/reports.php?type=project&project_id=<?php echo $report['project']['project_id']; ?>" class="btn btn-secondary">Project Status</a>
                <a href="<?php echo BASE_URL; ?>/reports.php?type=productivity&project_id=<?php echo $report['project']['project_id']; ?>" class="btn btn-secondary">Team Productivity</a>
            <?php endif; ?>
            <button class="btn btn-info dropdown-toggle" type="button" id="exportDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                Export
            </button>
            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="exportDropdown">
                <a class="dropdown-item export-report" href="#" data-format="pdf">Export as PDF</a>
                <a class="dropdown-item export-report" href="#" data-format="csv">Export as CSV</a>
                <a class="dropdown-item print-report" href="#">Print Report</a>
            </div>
        </div>
    </div>
</div>

<!-- Project selection -->
<?php if (!isset($report['project'])): ?>
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>/reports.php" class="form-inline report-filter-form">
            <input type="hidden" name="type" value="tickets">
            
            <div class="form-group mr-3">
                <label for="project_id" class="mr-2">Project:</label>
                <select id="project_id" name="project_id" class="form-control">
                    <option value="">All Projects</option>
                    <?php 
                    $projects = getUserProjects($userId);
                    foreach ($projects as $project): 
                        $selected = (isset($_GET['project_id']) && $_GET['project_id'] == $project['project_id']) ? 'selected' : '';
                    ?>
                        <option value="<?php echo $project['project_id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($project['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Filter</button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Status summary -->
<div class="card mb-4">
    <div class="card-header">
        <h3>Ticket Status Summary</h3>
    </div>
    <div class="card-body">
        <?php if (empty($report['status_data'])): ?>
            <div class="alert alert-info">No ticket data available.</div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-6">
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="table-responsive">
                        <table class="table table-striped" id="status-summary-table">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalTickets = array_sum(array_column($report['status_data'], 'count'));
                                foreach ($report['status_data'] as $status): 
                                    $percentage = ($totalTickets > 0) ? round(($status['count'] / $totalTickets) * 100, 1) : 0;
                                ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $status['status'])); ?>"><?php echo $status['status']; ?></span>
                                        </td>
                                        <td><?php echo $status['count']; ?></td>
                                        <td><?php echo $percentage; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="font-weight-bold">
                                    <td>Total</td>
                                    <td><?php echo $totalTickets; ?></td>
                                    <td>100%</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Status by priority -->
<div class="card mb-4">
    <div class="card-header">
        <h3>Status by Priority</h3>
    </div>
    <div class="card-body">
        <?php if (empty($report['status_data'])): ?>
            <div class="alert alert-info">No ticket data available.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped" id="status-priority-table">
                    <thead>
                        <tr>
                            <th>Status</th>
                            <th>Critical</th>
                            <th>Important</th>
                            <th>Nice to have</th>
                            <th>Feature Request</th>
                            <th>Low Priority</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['status_data'] as $status): ?>
                            <tr>
                                <td>
                                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $status['status'])); ?>"><?php echo $status['status']; ?></span>
                                </td>
                                <td><?php echo $status['critical_count']; ?></td>
                                <td><?php echo $status['important_count']; ?></td>
                                <td><?php echo $status['nice_count']; ?></td>
                                <td><?php echo $status['feature_count']; ?></td>
                                <td><?php echo $status['low_count']; ?></td>
                                <td><strong><?php echo $status['count']; ?></strong></td>
                            </tr>
                        <?php endforeach; ?>
                        <tr class="font-weight-bold">
                            <td>Total</td>
                            <td><?php echo array_sum(array_column($report['status_data'], 'critical_count')); ?></td>
                            <td><?php echo array_sum(array_column($report['status_data'], 'important_count')); ?></td>
                            <td><?php echo array_sum(array_column($report['status_data'], 'nice_count')); ?></td>
                            <td><?php echo array_sum(array_column($report['status_data'], 'feature_count')); ?></td>
                            <td><?php echo array_sum(array_column($report['status_data'], 'low_count')); ?></td>
                            <td><?php echo array_sum(array_column($report['status_data'], 'count')); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Visualization section -->
<div class="card mb-4">
    <div class="card-header">
        <h3>Distribution Visualization</h3>
    </div>
    <div class="card-body">
        <?php if (empty($report['status_data'])): ?>
            <div class="alert alert-info">No ticket data available for visualization.</div>
        <?php else: ?>
            <div class="row">
                <div class="col-md-6">
                    <h4 class="text-center">Priority Distribution</h4>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="priorityDistributionChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <h4 class="text-center">Status Distribution over Time</h4>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="statusTimeChart"></canvas>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recommendations -->
<div class="card">
    <div class="card-header">
        <h3>Recommendations</h3>
    </div>
    <div class="card-body">
        <?php if (empty($report['status_data'])): ?>
            <div class="alert alert-info">No data available for recommendations.</div>
        <?php else: ?>
            <?php
            // Generate recommendations based on status data
            $criticalOpen = 0;
            $oldTickets = 0;
            $stuckTickets = 0;
            
            foreach ($report['status_data'] as $status) {
                if ($status['status'] !== 'Complete' && $status['status'] !== 'Rejected' && $status['status'] !== 'Ignored') {
                    $criticalOpen += $status['critical_count'];
                }
                
                if ($status['status'] === 'Needs clarification' || $status['status'] === 'In review') {
                    $stuckTickets += $status['count'];
                }
            }
            ?>
            
            <div class="list-group">
                <?php if ($criticalOpen > 0): ?>
                    <div class="list-group-item list-group-item-action flex-column align-items-start list-group-item-warning">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">Critical Tickets Need Attention</h5>
                            <span class="badge badge-pill badge-warning"><?php echo $criticalOpen; ?> tickets</span>
                        </div>
                        <p class="mb-1">There are <?php echo $criticalOpen; ?> critical tickets that are not yet completed. These should be prioritized immediately.</p>
                    </div>
                <?php endif; ?>
                
                <?php if ($stuckTickets > 0): ?>
                    <div class="list-group-item list-group-item-action flex-column align-items-start list-group-item-info">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">Potentially Stalled Tickets</h5>
                            <span class="badge badge-pill badge-info"><?php echo $stuckTickets; ?> tickets</span>
                        </div>
                        <p class="mb-1">There are <?php echo $stuckTickets; ?> tickets that may be stalled in "Needs clarification" or "In review" status. Consider following up on these tickets.</p>
                    </div>
                <?php endif; ?>
                
                <?php
                // Calculate overall health
                $completedPercentage = ($totalTickets > 0) ? 
                    (array_sum(array_map(function($status) { 
                        return ($status['status'] === 'Complete') ? $status['count'] : 0; 
                    }, $report['status_data'])) / $totalTickets) * 100 : 0;
                
                $healthClass = '';
                $healthMessage = '';
                
                if ($completedPercentage >= 70) {
                    $healthClass = 'list-group-item-success';
                    $healthMessage = 'Project is in good health with a high completion rate.';
                } elseif ($completedPercentage >= 40) {
                    $healthClass = 'list-group-item-primary';
                    $healthMessage = 'Project is making steady progress.';
                } else {
                    $healthClass = 'list-group-item-danger';
                    $healthMessage = 'Project has a low completion rate and may need attention.';
                }
                ?>
                
                <div class="list-group-item list-group-item-action flex-column align-items-start <?php echo $healthClass; ?>">
                    <div class="d-flex w-100 justify-content-between">
                        <h5 class="mb-1">Overall Health Assessment</h5>
                        <span class="badge badge-pill badge-light"><?php echo round($completedPercentage, 1); ?>% complete</span>
                    </div>
                    <p class="mb-1"><?php echo $healthMessage; ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js for the charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4"></script>
<script>
$(document).ready(function() {
    // Status chart
    <?php if (!empty($report['status_data'])): ?>
    var statusCtx = document.getElementById('statusChart').getContext('2d');
    var statusLabels = [<?php echo "'" . implode("', '", array_column($report['status_data'], 'status')) . "'"; ?>];
    var statusData = [<?php echo implode(", ", array_column($report['status_data'], 'count')); ?>];
    
    var statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
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
    
    // Priority distribution chart
    var priorityCtx = document.getElementById('priorityDistributionChart').getContext('2d');
    
    var criticalData = [<?php echo implode(", ", array_column($report['status_data'], 'critical_count')); ?>];
    var importantData = [<?php echo implode(", ", array_column($report['status_data'], 'important_count')); ?>];
    var niceData = [<?php echo implode(", ", array_column($report['status_data'], 'nice_count')); ?>];
    var featureData = [<?php echo implode(", ", array_column($report['status_data'], 'feature_count')); ?>];
    var lowData = [<?php echo implode(", ", array_column($report['status_data'], 'low_count')); ?>];
    
    var priorityChart = new Chart(priorityCtx, {
        type: 'bar',
        data: {
            labels: statusLabels,
            datasets: [
                {
                    label: 'Critical',
                    data: criticalData,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Important',
                    data: importantData,
                    backgroundColor: 'rgba(253, 126, 20, 0.7)',
                    borderColor: 'rgba(253, 126, 20, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Nice to have',
                    data: niceData,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Feature Request',
                    data: featureData,
                    backgroundColor: 'rgba(23, 162, 184, 0.7)',
                    borderColor: 'rgba(23, 162, 184, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Low Priority',
                    data: lowData,
                    backgroundColor: 'rgba(108, 117, 125, 0.7)',
                    borderColor: 'rgba(108, 117, 125, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                xAxes: [{
                    stacked: true
                }],
                yAxes: [{
                    stacked: true,
                    ticks: {
                        beginAtZero: true
                    }
                }]
            }
        }
    });
    
    // Simulated status over time chart
    // This would typically use actual time series data from the database
    var timeCtx = document.getElementById('statusTimeChart').getContext('2d');
    
    // Simulated months (in a real app, this would come from the database)
    var months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
    
    var statusTimeChart = new Chart(timeCtx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [
                {
                    label: 'New',
                    data: [10, 8, 6, 5, 4, 3],
                    borderColor: '#17a2b8',
                    backgroundColor: 'rgba(23, 162, 184, 0.1)',
                    borderWidth: 2,
                    fill: true
                },
                {
                    label: 'In Progress',
                    data: [5, 7, 8, 9, 7, 5],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    borderWidth: 2,
                    fill: true
                },
                {
                    label: 'Complete',
                    data: [2, 5, 8, 12, 16, 22],
                    borderColor: '#28a745',
                    backgroundColor: 'rgba(40, 167, 69, 0.1)',
                    borderWidth: 2,
                    fill: true
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                yAxes: [{
                    ticks: {
                        beginAtZero: true
                    },
                    stacked: true
                }]
            }
        }
    });
    <?php endif; ?>
    
    // Export functionality
    $('.export-report').click(function(e) {
        e.preventDefault();
        var format = $(this).data('format');
        
        // Add export parameter to current URL
        var currentUrl = window.location.href;
        var separator = currentUrl.indexOf('?') > -1 ? '&' : '?';
        var exportUrl = currentUrl + separator + 'export=' + format;
        
        // Redirect to export URL
        window.location.href = exportUrl;
    });
    
    // Print functionality
    $('.print-report').click(function(e) {
        e.preventDefault();
        window.print();
    });
});
</script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>