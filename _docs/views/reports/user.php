<?php
// views/reports/user.php
// User activity report template

// Set page title
$pageTitle = 'User Activity Report: ' . $report['user']['first_name'] . ' ' . $report['user']['last_name'];

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row mb-3">
    <div class="col-8">
        <h1>User Activity Report</h1>
        <h2><?php echo htmlspecialchars($report['user']['first_name'] . ' ' . $report['user']['last_name']); ?></h2>
    </div>
    <div class="col-4 text-right">
        <div class="btn-group">
            <a href="<?php echo BASE_URL; ?>/reports.php" class="btn btn-secondary">Overview</a>
            <?php if (isset($_GET['project_id'])): ?>
                <a href="<?php echo BASE_URL; ?>/reports.php?type=project&project_id=<?php echo $_GET['project_id']; ?>" class="btn btn-secondary">Project Status</a>
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

<!-- Filter form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?php echo BASE_URL; ?>/reports.php" class="form-inline report-filter-form">
            <input type="hidden" name="type" value="user">
            <input type="hidden" name="user_id" value="<?php echo $report['user']['user_id']; ?>">
            
            <?php if (isset($_GET['project_id'])): ?>
                <input type="hidden" name="project_id" value="<?php echo $_GET['project_id']; ?>">
            <?php else: ?>
                <div class="form-group mr-3">
                    <label for="project_id" class="mr-2">Project:</label>
                    <select id="project_id" name="project_id" class="form-control">
                        <option value="">All Projects</option>
                        <?php 
                        $projects = getUserProjects($report['user']['user_id']);
                        foreach ($projects as $project): 
                            $selected = (isset($_GET['project_id']) && $_GET['project_id'] == $project['project_id']) ? 'selected' : '';
                        ?>
                            <option value="<?php echo $project['project_id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($project['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            
            <div class="form-group mr-3">
                <label for="start_date" class="mr-2">From:</label>
                <input type="date" id="start_date" name="start_date" class="form-control" value="<?php echo isset($_GET['start_date']) ? $_GET['start_date'] : ''; ?>">
            </div>
            
            <div class="form-group mr-3">
                <label for="end_date" class="mr-2">To:</label>
                <input type="date" id="end_date" name="end_date" class="form-control" value="<?php echo isset($_GET['end_date']) ? $_GET['end_date'] : ''; ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Filter</button>
            
            <?php if (isset($_GET['start_date']) || isset($_GET['end_date']) || isset($_GET['project_id'])): ?>
                <a href="<?php echo BASE_URL; ?>/reports.php?type=user&user_id=<?php echo $report['user']['user_id']; ?>" class="btn btn-secondary ml-2">Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Activity summary -->
<div class="row mb-4">
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Tickets Created</h5>
                <p class="card-text display-4"><?php echo $report['created_count']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Tickets Completed</h5>
                <p class="card-text display-4"><?php echo $report['completed_count']; ?></p>
            </div>
        </div>
    </div>
    <div class="col-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Comments Made</h5>
                <p class="card-text display-4"><?php echo $report['comments_count']; ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Productivity chart -->
<div class="card mb-4">
    <div class="card-header">
        <h3>Activity Overview</h3>
    </div>
    <div class="card-body">
        <div class="chart-container" style="position: relative; height:300px;">
            <canvas id="activityChart"></canvas>
        </div>
    </div>
</div>

<!-- Tickets created -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3>Tickets Created</h3>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#createdTicketsCollapse" aria-expanded="true" aria-controls="createdTicketsCollapse">
                <i class="fa fa-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="collapse show" id="createdTicketsCollapse">
        <div class="card-body">
            <?php if (empty($report['tickets_created'])): ?>
                <div class="alert alert-info">No tickets created in the selected period.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped" id="created-tickets-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Project</th>
                                <th>Status</th>
                                <th>Priority</th>
                                <th>Created On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['tickets_created'] as $ticket): ?>
                                <tr>
                                    <td>#<?php echo $ticket['ticket_id']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['project_name']); ?></td>
                                    <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $ticket['status_name'])); ?>"><?php echo $ticket['status_name']; ?></span></td>
                                    <td><td><span class="badge badge-priority-<?php echo strtolower(str_replace(' ', '-', $ticket['priority_name'])); ?>"><?php echo $ticket['priority_name']; ?></span></td></td>
                                    <td><?php echo formatDate($ticket['created_at'], 'M j, Y'); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/tickets.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-primary">View</a>
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

<!-- Tickets completed -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3>Tickets Completed</h3>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#completedTicketsCollapse" aria-expanded="true" aria-controls="completedTicketsCollapse">
                <i class="fa fa-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="collapse show" id="completedTicketsCollapse">
        <div class="card-body">
            <?php if (empty($report['tickets_completed'])): ?>
                <div class="alert alert-info">No tickets completed in the selected period.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped" id="completed-tickets-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Project</th>
                                <th>Priority</th>
                                <th>Completed On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['tickets_completed'] as $ticket): ?>
                                <tr>
                                    <td>#<?php echo $ticket['ticket_id']; ?></td>
                                    <td><?php echo htmlspecialchars($ticket['title']); ?></td>
                                    <td><?php echo htmlspecialchars($ticket['project_name']); ?></td>
                                    <td><td><span class="badge badge-priority-<?php echo strtolower(str_replace(' ', '-', $ticket['priority_name'])); ?>"><?php echo $ticket['priority_name']; ?></span></td></td>
                                    <td><?php echo formatDate($ticket['updated_at'], 'M j, Y'); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/tickets.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-primary">View</a>
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

<!-- Comments -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3>Comments</h3>
        <div class="btn-group">
            <button class="btn btn-sm btn-outline-secondary" type="button" data-toggle="collapse" data-target="#commentsCollapse" aria-expanded="true" aria-controls="commentsCollapse">
                <i class="fa fa-chevron-down"></i>
            </button>
        </div>
    </div>
    <div class="collapse show" id="commentsCollapse">
        <div class="card-body">
            <?php if (empty($report['comments'])): ?>
                <div class="alert alert-info">No comments in the selected period.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped" id="comments-table">
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Project</th>
                                <th>Comment</th>
                                <th>Created On</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($report['comments'] as $comment): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($comment['ticket_title']); ?></td>
                                    <td><?php echo htmlspecialchars($comment['project_name']); ?></td>
                                    <td><?php echo nl2br(htmlspecialchars(substr($comment['description'], 0, 100) . (strlen($comment['description']) > 100 ? '...' : ''))); ?></td>
                                    <td><?php echo formatDate($comment['created_at'], 'M j, Y'); ?></td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/tickets.php?id=<?php echo $comment['ticket_id']; ?>" class="btn btn-sm btn-primary">View Ticket</a>
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

<!-- Chart.js for activity chart -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4"></script>
<script>
$(document).ready(function() {
    // Create activity chart
    var activityCtx = document.getElementById('activityChart').getContext('2d');
    
    // Prepare data for chart
    var chartData = {
        labels: ['Tickets Created', 'Tickets Completed', 'Comments'],
        datasets: [{
            label: 'Activity Count',
            data: [
                <?php echo $report['created_count']; ?>, 
                <?php echo $report['completed_count']; ?>, 
                <?php echo $report['comments_count']; ?>
            ],
            backgroundColor: [
                'rgba(54, 162, 235, 0.7)',
                'rgba(75, 192, 192, 0.7)',
                'rgba(153, 102, 255, 0.7)'
            ],
            borderColor: [
                'rgba(54, 162, 235, 1)',
                'rgba(75, 192, 192, 1)',
                'rgba(153, 102, 255, 1)'
            ],
            borderWidth: 1
        }]
    };
    
    var activityChart = new Chart(activityCtx, {
        type: 'bar',
        data: chartData,
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