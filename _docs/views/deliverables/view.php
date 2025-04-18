<?php
// views/deliverables/view.php
// Deliverable view page template with priority-based grouping and enhanced drag-drop

// Get deliverable ID from URL
$deliverableId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if deliverable ID is provided
if (empty($deliverableId)) {
    setFlashMessage('error', 'Deliverable ID is required.');
    redirect(BASE_URL . '/projects.php');
}

// Get deliverable info
$deliverable = getDeliverable($deliverableId);

if (!$deliverable) {
    setFlashMessage('error', 'Deliverable not found.');
    redirect(BASE_URL . '/projects.php');
}

// Get project info for breadcrumbs
$project = getProject($deliverable['project_id']);

// Check if user has access to this project
$userId = getCurrentUserId();
$userRole = getUserProjectRole($userId, $project['project_id']);

if (!$userRole) {
    setFlashMessage('error', 'You do not have access to this deliverable.');
    redirect(BASE_URL . '/projects.php');
}

// Set page title
$pageTitle = 'Deliverable: ' . $deliverable['name'];

// Add styles and scripts
$additionalStyles = [
    BASE_URL . '/assets/css/deliverable-view.css'
];

$additionalScripts = [
    BASE_URL . '/assets/js/deliverable-view.js'
];

// Include header
require_once ROOT_PATH . '/views/includes/header.php';

// First, query the database to get the actual priority names and their IDs
$conn = getDbConnection();
$stmt = $conn->prepare("SELECT priority_id, name FROM priorities ORDER BY priority_id");
$stmt->execute();
$prioritiesResult = $stmt->get_result();

// Create the priority order mapping from the database results
$priorityOrder = [];
$priorityGroups = [];

while ($priority = $prioritiesResult->fetch_assoc()) {
    $priorityName = $priority['name'];
    $priorityId = $priority['priority_id'];
    
    // Add to the ordering map
    $priorityOrder[$priorityName] = $priorityId;
    
    // Initialize the priority group
    $priorityGroups[$priorityName] = [
        'name' => $priorityName,
        'order' => $priorityId, // Use the priority_id as the order
        'tickets' => []
    ];
}

// If no priorities found in the database (fallback), use hardcoded priorities
if (empty($priorityGroups)) {
    $priorityOrder = [
        '1-Critical' => 1,
        '2-Important' => 2,
        '3-Nice to have' => 3,
        '4-Feature Request' => 4,
        '5-Cosmetic' => 5,
        '6-Not set' => 6
    ];
    
    foreach ($priorityOrder as $priorityName => $order) {
        $priorityGroups[$priorityName] = [
            'name' => $priorityName,
            'order' => $order,
            'tickets' => []
        ];
    }
}

// Group tickets by priority
if (!empty($deliverable['tickets'])) {
    foreach ($deliverable['tickets'] as $ticket) {
        $priorityName = $ticket['priority_name'];
        
        // If this priority doesn't exist in our groups, create it
        if (!isset($priorityGroups[$priorityName])) {
            // Try to extract a numeric value from the beginning of the priority name
            if (preg_match('/^(\d+)/', $priorityName, $matches)) {
                $order = (int)$matches[1];
            } else {
                $order = 999; // Fallback order for priorities without numeric prefix
            }
            
            $priorityGroups[$priorityName] = [
                'name' => $priorityName,
                'order' => $order,
                'tickets' => []
            ];
        }
        
        // Add the ticket to its priority group
        $priorityGroups[$priorityName]['tickets'][] = $ticket;
    }
}

// Sort priority groups by their order value
uasort($priorityGroups, function($a, $b) {
    return $a['order'] - $b['order'];
});
?>

<div class="row mb-2">
    <!-- Breadcrumbs -->
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/projects.php">Projects</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $project['project_id']; ?>"><?php echo htmlspecialchars($project['name']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($deliverable['name']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-9">
        <div class="card mb-3">
            <div class="card-header">
                <div class="row">
                    <div class="col-8">
                        <h2><?php echo htmlspecialchars($deliverable['name']); ?></h2>
                    </div>
                    <div class="col-4 text-right">
                        <?php if ($userRole !== 'Viewer' && $userRole !== 'Tester'): ?>
                            <a href="<?php echo BASE_URL; ?>/deliverables.php?action=edit&id=<?php echo $deliverableId; ?>" class="btn btn-secondary btn-sm">Edit</a>
                            <a href="<?php echo BASE_URL; ?>/tickets.php?action=create&deliverable_id=<?php echo $deliverableId; ?>" class="btn btn-primary btn-sm">Add Ticket</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <p class="mb-3"><?php echo nl2br(htmlspecialchars($deliverable['description'])); ?></p>
                
                <!-- Compact Ticket filtering options - true 2x2 layout -->
                <div class="ticket-filters mb-3">
                    <div class="card">
                        <div class="card-body p-2">
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <select id="filter-status" class="form-control form-control-sm" placeholder="Status">
                                        <option value="">All Statuses</option>
                                        <option value="open" selected>Open Tickets</option>
                                        <option value="closed">Closed Tickets</option>
                                        <option value="" disabled>──────────</option>
                                        <option value="New">New</option>
                                        <option value="Needs clarification">Needs clarification</option>
                                        <option value="Assigned">Assigned</option>
                                        <option value="In progress">In progress</option>
                                        <option value="In review">In review</option>
                                        <option value="Complete">Complete</option>
                                        <option value="Rejected">Rejected</option>
                                        <option value="Ignored">Ignored</option>
                                    </select>
                                </div>
                                <div class="col-6 mb-2">
                                    <select id="filter-priority" class="form-control form-control-sm">
                                        <option value="">All Priorities</option>
                                        <option value="1-Critical">1-Critical</option>
                                        <option value="1-Important">1-Important</option>
                                        <option value="2-Nice to have">2-Nice to have</option>
                                        <option value="3-Feature Request">3-Feature Request</option>
                                        <option value="4-Nice to have">4-Nice to have</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <select id="filter-assignee" class="form-control form-control-sm">
                                        <option value="">All Assignees</option>
                                        <option value="me">Assigned to me</option>
                                        <option value="" disabled>──────────</option>
                                        <option value="unassigned">Unassigned</option>
                                        <?php
                                        $projectUsers = getProjectUsers($project['project_id']);
                                        foreach ($projectUsers as $user): 
                                            echo '<option value="' . $user['user_id'] . '">' . htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) . '</option>';
                                        endforeach;
                                        ?>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <input type="text" id="ticket-search" class="form-control form-control-sm" placeholder="Search...">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Tickets grouped by priority -->
                <div class="tickets-container">
                    <h3 class="mb-2">Tickets by Priority</h3>
                    
                    <?php if (empty($deliverable['tickets'])): ?>
                        <div class="alert alert-info">No tickets have been added to this deliverable yet.</div>
                    <?php else: ?>
                        <?php foreach ($priorityGroups as $priorityKey => $priorityGroup): ?>
                            <?php if (empty($priorityGroup['tickets'])) continue; ?>
                            <div class="priority-group mb-4" data-priority="<?php echo htmlspecialchars($priorityKey); ?>">
                                <div class="priority-header">
                                    <span class="badge badge-priority-<?php echo strtolower(str_replace(' ', '-', $priorityKey)); ?>"><?php echo htmlspecialchars($priorityKey); ?></span>
                                    <span class="ticket-count"><?php echo count($priorityGroup['tickets']); ?> tickets</span>
                                </div>
                                <div class="priority-tickets-container sortable-tickets" 
                                     data-priority="<?php echo htmlspecialchars($priorityKey); ?>"
                                     data-deliverable-id="<?php echo $deliverableId; ?>">
                                    <?php foreach ($priorityGroup['tickets'] as $ticket): ?>
                                        <div class="card mb-2 ticket-card" 
                                             data-id="<?php echo $ticket['ticket_id']; ?>" 
                                             data-status="<?php echo htmlspecialchars($ticket['status_name']); ?>"
                                             data-status-id="<?php echo $ticket['status_id']; ?>"
                                             data-priority="<?php echo htmlspecialchars($ticket['priority_name']); ?>"
                                             data-priority-id="<?php echo $ticket['priority_id']; ?>"
                                             data-assignee="<?php echo $ticket['assigned_to']; ?>">
                                            <div class="card-body p-2">
                                                <h5 class="mb-1">
                                                    <span class="drag-handle"></span>
                                                    <a href="<?php echo BASE_URL; ?>/tickets.php?id=<?php echo $ticket['ticket_id']; ?>" class="text-dark ticket-title">
                                                        <?php echo htmlspecialchars($ticket['title']); ?>
                                                    </a>
                                                </h5>
                                                <div class="ticket-meta-inline">
                                                    <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $ticket['status_name'])); ?>"><?php echo $ticket['status_name']; ?></span>
                                                    <?php if (!empty($ticket['assigned_name'])): ?>
                                                        <small class="text-muted ml-2">Assigned: <?php echo htmlspecialchars($ticket['assigned_name']); ?></small>
                                                    <?php else: ?>
                                                        <small class="text-muted ml-2">Unassigned</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="no-tickets-message" style="display: none;">
                            <div class="alert alert-info">No tickets meet your current filter options</div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-3">
        <!-- Deliverable Info Sidebar -->
        <div class="card mb-3">
            <div class="card-header py-2">
                <h3 class="mb-0">Deliverable Info</h3>
            </div>
            <div class="card-body">
                <p class="mb-1">
                    <strong>Created by:</strong> <?php echo htmlspecialchars($deliverable['first_name'] . ' ' . $deliverable['last_name']); ?>
                </p>
                <p class="mb-2">
                    <strong>Created on:</strong> <?php echo formatDate($deliverable['created_at'], 'M j, Y'); ?>
                </p>
                
                <?php
                // Calculate statistics
                $totalTickets = count($deliverable['tickets']);
                $openTickets = 0;
                $completeTickets = 0;
                
                foreach ($deliverable['tickets'] as $ticket) {
                    if ($ticket['status_id'] === 6) {
                        $completeTickets++;
                    } elseif ($ticket['status_id'] !== 7 && $ticket['status_id'] !== 8) {
                        $openTickets++;
                    }
                }
                
                $completionRate = ($totalTickets > 0) ? round(($completeTickets / $totalTickets) * 100) : 0;
                ?>
                
                <div class="deliverable-stats mt-2">
                    <div class="stat-item mb-1">
                        <div class="d-flex justify-content-between">
                            <span class="stat-label">Total Tickets:</span>
                            <span class="stat-value"><?php echo $totalTickets; ?></span>
                        </div>
                    </div>
                    <div class="stat-item mb-1">
                        <div class="d-flex justify-content-between">
                            <span class="stat-label">Open Tickets:</span>
                            <span class="stat-value"><?php echo $openTickets; ?></span>
                        </div>
                    </div>
                    <div class="stat-item mb-1">
                        <div class="d-flex justify-content-between">
                            <span class="stat-label">Complete Tickets:</span>
                            <span class="stat-value"><?php echo $completeTickets; ?></span>
                        </div>
                    </div>
                    <div class="stat-item mb-1">
                        <div class="d-flex justify-content-between">
                            <span class="stat-label">Completion Rate:</span>
                            <span class="stat-value"><?php echo $completionRate; ?>%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Status Legend -->
        <div class="card mb-3">
            <div class="card-header py-2">
                <h3 class="mb-0">Status Legend</h3>
            </div>
            <div class="card-body px-3 py-2">
                <?php
                $statuses = ['New', 'Needs clarification', 'Assigned', 'In progress', 'In review', 'Complete', 'Rejected', 'Ignored'];
                foreach ($statuses as $status):
                    $statusClass = strtolower(str_replace(' ', '-', $status));
                ?>
                <div class="status-legend-item mb-1">
                    <span class="badge badge-<?php echo $statusClass; ?>"><?php echo $status; ?></span>
                    <small class="text-muted"><?php echo getStatusDescription($status); ?></small>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Return to Project -->
        <div class="card">
            <div class="card-body py-3">
                <a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $project['project_id']; ?>" class="btn btn-secondary btn-sm btn-block mb-2">
                    ← Back to Project
                </a>
                
                <?php if ($userRole !== 'Viewer' && $userRole !== 'Tester'): ?>
                    <a href="<?php echo BASE_URL; ?>/tickets.php?action=create&deliverable_id=<?php echo $deliverableId; ?>" class="btn btn-primary btn-sm btn-block">
                        + Add New Ticket
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Initialize JavaScript variables needed by script -->
<script>
// Pass necessary PHP variables to JavaScript
var deliverableId = <?php echo $deliverableId; ?>;
var baseUrl = '<?php echo BASE_URL; ?>';
var currentUserId = <?php echo $userId; ?>;
</script>

<?php
// Helper function to get status description
function getStatusDescription($status) {
    switch ($status) {
        case 'New':
            return 'Not yet addressed';
        case 'Needs clarification':
            return 'More info needed';
        case 'Assigned':
            return 'Assigned but not started';
        case 'In progress':
            return 'Work has begun';
        case 'In review':
            return 'Awaiting review';
        case 'Complete':
            return 'Completed and verified';
        case 'Rejected':
            return 'Won\'t be implemented';
        case 'Ignored':
            return 'Intentionally set aside';
        default:
            return '';
    }
}
?>
<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>