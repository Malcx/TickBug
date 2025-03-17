<!--
views/tickets/list.php
List tickets template (for showing tickets assigned to user)
-->
<?php
// Set page title
$pageTitle = 'My Tickets';

// Include header
require_once ROOT_PATH . '/views/includes/header.php';

// Get tickets assigned to user
$assignedTickets = getUserAssignedTickets($userId);

// Get tickets created by user
$createdTickets = getUserCreatedTickets($userId);
?>

<div class="row mb-3">
    <div class="col-8">
        <h1>My Tickets</h1>
    </div>
    <div class="col-4 text-right">
        <a href="<?php echo BASE_URL; ?>/projects.php" class="btn btn-secondary">Back to Projects</a>
    </div>
</div>

<!-- Tabs navigation -->
<ul class="nav nav-tabs mb-3" id="ticketTabs" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="assigned-tab" data-toggle="tab" href="#assigned" role="tab" aria-controls="assigned" aria-selected="true">Assigned to Me (<?php echo count($assignedTickets); ?>)</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="created-tab" data-toggle="tab" href="#created" role="tab" aria-controls="created" aria-selected="false">Created by Me (<?php echo count($createdTickets); ?>)</a>
    </li>
</ul>

<!-- Tab content -->
<div class="tab-content" id="ticketTabsContent">
    <!-- Assigned tickets tab -->
    <div class="tab-pane fade show active" id="assigned" role="tabpanel" aria-labelledby="assigned-tab">
        <?php if (empty($assignedTickets)): ?>
            <div class="alert alert-info">You don't have any tickets assigned to you.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Project</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignedTickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo $ticket['ticket_id']; ?></td>
                                <td><a href="<?php echo BASE_URL; ?>/tickets.php?id=<?php echo $ticket['ticket_id']; ?>"><?php echo htmlspecialchars($ticket['title']); ?></a></td>
                                <td><a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $ticket['project_id']; ?>"><?php echo htmlspecialchars($ticket['project_name']); ?></a></td>
                                <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $ticket['status_name'])); ?>"><?php echo $ticket['status_name']; ?></span></td>
                                <td><span class="badge badge-priority-<?php echo strtolower(str_replace(' ', '-', $ticket['priority_name'])); ?>"><?php echo $ticket['priority_name']; ?></span></td>
                                <td><?php echo formatDate($ticket['created_at'], 'M j, Y'); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/tickets.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <?php if ($userRole !== 'Viewer' && $userRole !== 'Tester'): ?>
                                        <a href="<?php echo BASE_URL; ?>/tickets.php?action=edit&id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Created tickets tab -->
    <div class="tab-pane fade" id="created" role="tabpanel" aria-labelledby="created-tab">
        <?php if (empty($createdTickets)): ?>
            <div class="alert alert-info">You haven't created any tickets yet.</div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Project</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Assigned To</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($createdTickets as $ticket): ?>
                            <tr>
                                <td>#<?php echo $ticket['ticket_id']; ?></td>
                                <td><a href="<?php echo BASE_URL; ?>/tickets.php?id=<?php echo $ticket['ticket_id']; ?>"><?php echo htmlspecialchars($ticket['title']); ?></a></td>
                                <td><a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $ticket['project_id']; ?>"><?php echo htmlspecialchars($ticket['project_name']); ?></a></td>
                                <td><span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $ticket['status_name'])); ?>"><?php echo $ticket['status_name']; ?></span></td>
                                <td><td><span class="badge badge-priority-<?php echo strtolower(str_replace(' ', '-', $ticket['priority_name'])); ?>"><?php echo $ticket['priority_name']; ?></span></td></td>
                                <td><?php echo !empty($ticket['assigned_name']) ? htmlspecialchars($ticket['assigned_name']) : '<em>Unassigned</em>'; ?></td>
                                <td><?php echo formatDate($ticket['created_at'], 'M j, Y'); ?></td>
                                <td>
                                    <a href="<?php echo BASE_URL; ?>/tickets.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-primary">View</a>
                                    <a href="<?php echo BASE_URL; ?>/tickets.php?action=edit&id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>