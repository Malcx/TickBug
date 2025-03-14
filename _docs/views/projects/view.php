<!--
views/projects/view.php
Modified project view page template with collapsible deliverables
-->
<?php
// Set page title
$pageTitle = $project['name'];

// Include header
require_once ROOT_PATH . '/views/includes/header.php';

// Get deliverables
require_once ROOT_PATH . '/includes/deliverables.php';
$deliverables = getProjectDeliverables($project['project_id']);

// Get project users
require_once ROOT_PATH . '/includes/users.php';
$projectUsers = getProjectUsers($project['project_id']);

// Calculate project stats
$totalTickets = 0;
$openTickets = 0;

foreach ($deliverables as $deliverable) {
    if (isset($deliverable['tickets'])) {
        $totalTickets += count($deliverable['tickets']);
        foreach ($deliverable['tickets'] as $ticket) {
            if ($ticket['status'] !== 'Complete' && $ticket['status'] !== 'Rejected' && $ticket['status'] !== 'Ignored') {
                $openTickets++;
            }
        }
    }
}
?>

<div class="row mb-3">
    <div class="col-8">
        <h1><?php echo htmlspecialchars($project['name']); ?></h1>
        <p><?php echo htmlspecialchars($project['description']); ?></p>
        <div class="project-stats mb-3">
            <span class="badge badge-info">Total Tickets: <?php echo $totalTickets; ?></span>
            <span class="badge badge-warning">Open Tickets: <?php echo $openTickets; ?></span>
        </div>
    </div>
    <div class="col-4 text-right">
        <?php if ($userRole === 'Owner' || $userRole === 'Project Manager'): ?>
            <a href="<?php echo BASE_URL; ?>/projects.php?action=edit&id=<?php echo $project['project_id']; ?>" class="btn btn-secondary mb-2">Edit Project</a>
            <a href="<?php echo BASE_URL; ?>/projects.php?action=users&id=<?php echo $project['project_id']; ?>" class="btn btn-secondary mb-2">Manage Users</a>
        <?php endif; ?>
        <?php if ($userRole !== 'Viewer' && $userRole !== 'Tester'): ?>
            <a href="<?php echo BASE_URL; ?>/deliverables.php?action=create&project_id=<?php echo $project['project_id']; ?>" class="btn btn-primary">Add Deliverable</a>
        <?php endif; ?>
    </div>
</div>

<div class="row">
    <div class="col-9">
        <!-- Deliverables Section -->
        <h2>Deliverables</h2>
        
        <?php if (empty($deliverables)): ?>
            <div class="alert alert-info">
                No deliverables have been added to this project yet.
            </div>
        <?php else: ?>
            <div id="deliverables-container">
                <?php foreach ($deliverables as $deliverable): 
                    // Calculate ticket counts directly in PHP
                    $totalTickets = count($deliverable['tickets']);
                    $openTickets = 0;
                    
                    foreach ($deliverable['tickets'] as $ticket) {
                        if ($ticket['status'] !== 'Complete' && $ticket['status'] !== 'Rejected' && $ticket['status'] !== 'Ignored') {
                            $openTickets++;
                        }
                    }
                ?>
                    <div class="card mb-3 deliverable-card" data-id="<?php echo $deliverable['deliverable_id']; ?>">
                        <div class="card-header deliverable-header" style="cursor: pointer;">
                            <div class="row">
                                <div class="col-8 d-flex align-items-center">
                                    <h3><?php echo htmlspecialchars($deliverable['name']); ?></h3>
                                    <span class="ml-3 badge badge-info"><?php echo $openTickets; ?> open / <?php echo $totalTickets; ?> total</span>
                                    <span class="ml-2 navigate-icon">→</span>
                                </div>
                                <div class="col-4 text-right">
                                    <?php if ($userRole !== 'Viewer' && $userRole !== 'Tester'): ?>
                                        <a href="<?php echo BASE_URL; ?>/deliverables.php?action=edit&id=<?php echo $deliverable['deliverable_id']; ?>" class="btn btn-secondary btn-sm">Edit</a>
                                        <a href="<?php echo BASE_URL; ?>/tickets.php?action=create&deliverable_id=<?php echo $deliverable['deliverable_id']; ?>" class="btn btn-primary btn-sm">Add Ticket</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <p class="deliverable-description"><?php echo htmlspecialchars($deliverable['description']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-3">
        <!-- Project Team -->
        <div class="card">
            <div class="card-header">
                <h3>Project Team</h3>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    <?php foreach ($projectUsers as $user): ?>
                        <li class="list-group-item">
                            <div class="user-info">
                                <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                <span class="badge badge-secondary"><?php echo $user['role']; ?></span>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>



<!-- Include project scripts -->
<script src="<?php echo BASE_URL; ?>/assets/js/projects.js"></script>
<!-- Include our custom project view script -->
<script src="<?php echo BASE_URL; ?>/assets/js/project-view.js"></script>

<!-- Add custom CSS for project view -->
<link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/project-view.css">

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>