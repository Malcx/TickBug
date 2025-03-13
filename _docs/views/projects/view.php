<!--
views/projects/view.php
The project view page template
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
?>

<div class="row mb-3">
    <div class="col-8">
        <h1><?php echo htmlspecialchars($project['name']); ?></h1>
        <p><?php echo htmlspecialchars($project['description']); ?></p>
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
                <?php foreach ($deliverables as $deliverable): ?>
                    <div class="card mb-3 deliverable-card" data-id="<?php echo $deliverable['deliverable_id']; ?>">
                        <div class="card-header">
                            <div class="row">
                                <div class="col-8">
                                    <h3><?php echo htmlspecialchars($deliverable['name']); ?></h3>
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
                            <p><?php echo htmlspecialchars($deliverable['description']); ?></p>
                            
                            <?php if (isset($deliverable['tickets']) && !empty($deliverable['tickets'])): ?>
                                <h4>Tickets</h4>
                                <div class="tickets-container" data-deliverable-id="<?php echo $deliverable['deliverable_id']; ?>">
                                    <?php foreach ($deliverable['tickets'] as $ticket): ?>
                                        <div class="ticket-card draggable" data-id="<?php echo $ticket['ticket_id']; ?>">
                                            <div class="row">
                                                <div class="col-8">
                                                    <h5><a href="<?php echo BASE_URL; ?>/tickets.php?id=<?php echo $ticket['ticket_id']; ?>"><?php echo htmlspecialchars($ticket['title']); ?></a></h5>
                                                </div>
                                                <div class="col-4 text-right">
                                                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $ticket['status'])); ?>"><?php echo $ticket['status']; ?></span>
                                                    <span class="badge badge-priority-<?php echo strtolower(str_replace(' ', '-', $ticket['priority'])); ?>"><?php echo $ticket['priority']; ?></span>
                                                </div>
                                            </div>
                                            <p>
                                                <?php if (!empty($ticket['assigned_to'])): ?>
                                                    <small>Assigned to: <?php echo htmlspecialchars($ticket['assigned_name']); ?></small>
                                                <?php else: ?>
                                                    <small>Unassigned</small>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    No tickets have been added to this deliverable yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <div class="col-3">
        <!-- Users Section -->
        <div class="card mb-3">
            <div class="card-header">
                <h3>Project Users</h3>
            </div>
            <div class="card-body">
                <div id="project-users" class="drag-container">
                    <?php foreach ($projectUsers as $user): ?>
                        <div class="user-card" data-id="<?php echo $user['user_id']; ?>">
                            <div class="row">
                                <div class="col-12">
                                    <p>
                                        <strong><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></strong>
                                        <br>
                                        <small><?php echo $user['role']; ?></small>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- Status Section -->
        <div class="card">
            <div class="card-header">
                <h3>Ticket Status</h3>
            </div>
            <div class="card-body">
                <div id="status-container">
                    <?php
                    $statuses = ['New', 'Needs clarification', 'Assigned', 'In progress', 'In review', 'Complete', 'Rejected', 'Ignored'];
                    foreach ($statuses as $status):
                        $statusClass = strtolower(str_replace(' ', '-', $status));
                    ?>
                    <div class="status-card drag-container" data-status="<?php echo $status; ?>">
                        <div class="badge badge-<?php echo $statusClass; ?> mb-2"><?php echo $status; ?></div>
                        <p class="status-description"><?php echo getStatusDescription($status); ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to get status description
function getStatusDescription($status) {
    switch ($status) {
        case 'New':
            return 'Recently created, not yet addressed.';
        case 'Needs clarification':
            return 'More information needed before proceeding.';
        case 'Assigned':
            return 'Assigned to a team member but not started.';
        case 'In progress':
            return 'Work has begun on this ticket.';
        case 'In review':
            return 'Work completed, awaiting review.';
        case 'Complete':
            return 'Fully completed and verified.';
        case 'Rejected':
            return 'Cannot or will not be implemented.';
        case 'Ignored':
            return 'Intentionally set aside.';
        default:
            return '';
    }
}
?>

<script src="<?php echo BASE_URL; ?>/assets/js/projects.js"></script>
<script src="<?php echo BASE_URL; ?>/assets/js/drag-drop.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>