<?php
// views/reports/productivity.php
// Team productivity report template

// Set page title
$pageTitle = isset($report['project']) ? 'Team Productivity Report: ' . $report['project']['name'] : 'Overall Team Productivity Report';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row mb-3">
    <div class="col-8">
        <h1>Team Productivity Report</h1>
        <?php if (isset($report['project'])): ?>
            <h2><?php echo htmlspecialchars($report['project']['name']); ?></h2>
        <?php endif; ?>
    </div>
    <div class="col-4 text-right">
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>/reports.php" class="btn btn-secondary">Overview</a>
            <?php if (isset($report['project'])): ?>
                <a href="<?php echo BASE_URL; ?>/reports.php?type=project&project_id=<?php echo $report['project']['project_id']; ?>" class="btn btn-secondary">Project Status</a>
                <a href="<?php echo BASE_URL; ?>/reports.php?type=tickets&project_id=<?php echo $report['project']['project_id']; ?>" class="btn btn-secondary">Tickets by Status</a>
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
            <input type="hidden" name="type" value="productivity">
            
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

<!-- Date range selector -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>/reports.php" class="form-inline report-filter-form">
            <input type="hidden" name="type" value="productivity">
            <?php if (isset($report['project'])): ?>
                <input type="hidden" name="project_id" value="<?php echo $report['project']['project_id']; ?>">
            <?php endif; ?>
            
            <div class="form-group mr-3">
                <label for="start_date" class="mr-2">Start Date:</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? htmlspecialchars($_GET['start_date'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>

            <div class="form-group mr-3">
                <label for="end_date" class="mr-2">End Date:</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? htmlspecialchars($_GET['end_date'], ENT_QUOTES, 'UTF-8') : ''; ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Apply Date Range</button>
            
            <?php if (isset($_GET['start_date']) || isset($_GET['end_date'])): ?>
                <?php 
                    $clearUrl = BASE_URL . '/reports.php?type=productivity';
                    if (isset($report['project'])) {
                        $clearUrl .= '&project_id=' . $report['project']['project_id'];
                    }
                ?>
                <a href="<?php echo $clearUrl; ?>" class="btn btn-secondary ml-2">Clear Dates</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Team Performance Table -->
<div class="card mb-4">
    <div class="card-header">
        <h3>Team Member Performance</h3>
        <?php if (isset($report['start_date']) && isset($report['end_date'])): ?>
            <p class="text-muted">
                Date range: <?php echo date('M j, Y', strtotime($report['start_date'])); ?> to 
                <?php echo date('M j, Y', strtotime($report['end_date'])); ?>
            </p>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (empty($report['user_data'])): ?>
            <div class="alert alert-info">No user data available for the selected period.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped" id="productivity-table">
                    <thead>
                        <tr>
                            <th>Team Member</th>
                            <th>Total Tickets</th>
                            <th>Completed</th>
                            <th>Open</th>
                            <th>Critical</th>
                            <th>Comments</th>
                            <th>Completion Rate</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($report['user_data'] as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                <td><?php echo $user['total_tickets']; ?></td>
                                <td><?php echo $user['completed_tickets']; ?></td>
                                <td><?php echo $user['open_tickets']; ?></td>
                                <td><?php echo $user['critical_tickets']; ?></td>
                                <td><?php echo $user['comment_count']; ?></td>
                                <td>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $user['completion_rate']; ?>%;" 
                                             aria-valuenow="<?php echo $user['completion_rate']; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $user['completion_rate']; ?>%
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php
                                        $userReportUrl = BASE_URL . '/reports.php?type=user&user_id=' . $user['user_id'];
                                        if (isset($report['project'])) {
                                            $userReportUrl .= '&project_id=' . $report['project']['project_id'];
                                        }
                                        if (isset($_GET['start_date'])) {
                                            $userReportUrl .= '&start_date=' . urlencode($_GET['start_date']);
                                        }
                                        if (isset($_GET['end_date'])) {
                                            $userReportUrl .= '&end_date=' . urlencode($_GET['end_date']);
                                        }
                                    ?>
                                    <a href="<?php echo $userReportUrl; ?>" class="btn btn-sm btn-primary">View Details</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Productivity Visualizations -->
<div class="row">
    <!-- Completion Rate Chart -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Completion Rates</h3>
            </div>
            <div class="card-body">
                <?php if (empty($report['user_data'])): ?>
                    <div class="alert alert-info">No data available for visualization.</div>
                <?php else: ?>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="completionRateChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Ticket Distribution Chart -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Ticket Distribution</h3>
            </div>
            <div class="card-body">
                <?php if (empty($report['user_data'])): ?>
                    <div class="alert alert-info">No data available for visualization.</div>
                <?php else: ?>
                    <div class="chart-container" style="position: relative; height:300px;">
                        <canvas id="ticketDistributionChart"></canvas>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Team Analysis -->
<div class="card">
    <div class="card-header">
        <h3>Team Analysis</h3>
    </div>
    <div class="card-body">
        <?php if (empty($report['user_data'])): ?>
            <div class="alert alert-info">No data available for analysis.</div>
        <?php else: ?>
            <?php
                // Calculate team statistics
                $totalCompleted = array_sum(array_column($report['user_data'], 'completed_tickets'));
                $totalOpen = array_sum(array_column($report['user_data'], 'open_tickets'));
                $totalTickets = array_sum(array_column($report['user_data'], 'total_tickets'));
                $avgCompletionRate = ($totalTickets > 0) ? round(($totalCompleted / $totalTickets) * 100, 1) : 0;
                
                // Find user with highest completion rate
                $highestCompletion = ['user' => null, 'rate' => 0];
                foreach ($report['user_data'] as $user) {
                    if ($user['total_tickets'] > 0 && $user['completion_rate'] > $highestCompletion['rate']) {
                        $highestCompletion = [
                            'user' => $user['first_name'] . ' ' . $user['last_name'],
                            'rate' => $user['completion_rate']
                        ];
                    }
                }
                
                // Find user with most critical tickets completed
                $mostCritical = ['user' => null, 'count' => 0];
                foreach ($report['user_data'] as $user) {
                    if ($user['critical_tickets'] > $mostCritical['count']) {
                        $mostCritical = [
                            'user' => $user['first_name'] . ' ' . $user['last_name'],
                            'count' => $user['critical_tickets']
                        ];
                    }
                }
            ?>
            
            <div class="row">
                <div class="col-md-6">
                    <h4>Team Summary</h4>
                    <ul class="list-group mb-4">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Team Size
                            <span class="badge badge-primary badge-pill"><?php echo count($report['user_data']); ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Total Tickets
                            <span class="badge badge-primary badge-pill"><?php echo $totalTickets; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Completed Tickets
                            <span class="badge badge-success badge-pill"><?php echo $totalCompleted; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Open Tickets
                            <span class="badge badge-warning badge-pill"><?php echo $totalOpen; ?></span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Average Completion Rate
                            <span class="badge badge-info badge-pill"><?php echo $avgCompletionRate; ?>%</span>
                        </li>
                    </ul>
                </div>
                
                <div class="col-md-6">
                    <h4>Team Insights</h4>
                    <div class="card">
                        <div class="card-body">
                            <?php if ($highestCompletion['user']): ?>
                                <p><strong>Highest Completion Rate:</strong> <?php echo htmlspecialchars($highestCompletion['user']); ?> (<?php echo $highestCompletion['rate']; ?>%)</p>
                            <?php endif; ?>
                            
                            <?php if ($mostCritical['user']): ?>
                                <p><strong>Most Critical Tickets:</strong> <?php echo htmlspecialchars($mostCritical['user']); ?> (<?php echo $mostCritical['count']; ?> tickets)</p>
                            <?php endif; ?>
                            
                            <p><strong>Team Health:</strong> 
                                <?php
                                    if ($avgCompletionRate >= 75) {
                                        echo '<span class="text-success">Excellent</span> - The team is performing very well with a high completion rate.';
                                    } elseif ($avgCompletionRate >= 50) {
                                        echo '<span class="text-info">Good</span> - The team has a solid completion rate, but there is room for improvement.';
                                    } elseif ($avgCompletionRate >= 25) {
                                        echo '<span class="text-warning">Fair</span> - The team needs to focus on improving their completion rate.';
                                    } else {
                                        echo '<span class="text-danger">Needs Attention</span> - The team has a low completion rate and requires immediate attention.';
                                    }
                                ?>
                            </p>
                            
                            <?php if ($totalTickets > 0): ?>
                                <p><strong>Recommendations:</strong></p>
                                <ul>
                                    <?php if ($avgCompletionRate < 50): ?>
                                        <li>Consider redistributing tickets more evenly among team members.</li>
                                        <li>Schedule a team meeting to discuss and address any blockers.</li>
                                    <?php endif; ?>
                                    <?php if ($totalOpen > $totalCompleted): ?>
                                        <li>Focus on completing open tickets before starting new ones.</li>
                                    <?php endif; ?>
                                    <li>Regular progress updates may help keep the team on track.</li>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Chart.js for visualizations -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4"></script>
<script>
$(document).ready(function() {
    <?php if (!empty($report['user_data'])): ?>
    // Prepare data for charts
    var userNames = [<?php echo "'" . implode("', '", array_map(function($user) { return $user['first_name'] . ' ' . $user['last_name']; }, $report['user_data'])) . "'"; ?>];
    var completionRates = [<?php echo implode(", ", array_column($report['user_data'], 'completion_rate')); ?>];
    var completedTickets = [<?php echo implode(", ", array_column($report['user_data'], 'completed_tickets')); ?>];
    var openTickets = [<?php echo implode(", ", array_column($report['user_data'], 'open_tickets')); ?>];
    
    // Completion Rate Chart
    var completionCtx = document.getElementById('completionRateChart').getContext('2d');
    var completionChart = new Chart(completionCtx, {
        type: 'horizontalBar',
        data: {
            labels: userNames,
            datasets: [{
                label: 'Completion Rate (%)',
                data: completionRates,
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                xAxes: [{
                    ticks: {
                        beginAtZero: true,
                        max: 100
                    }
                }]
            }
        }
    });
    
    // Ticket Distribution Chart
    var distributionCtx = document.getElementById('ticketDistributionChart').getContext('2d');
    var distributionChart = new Chart(distributionCtx, {
        type: 'bar',
        data: {
            labels: userNames,
            datasets: [
                {
                    label: 'Completed Tickets',
                    data: completedTickets,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Open Tickets',
                    data: openTickets,
                    backgroundColor: 'rgba(255, 193, 7, 0.7)',
                    borderColor: 'rgba(255, 193, 7, 1)',
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