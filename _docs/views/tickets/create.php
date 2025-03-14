<!--
views/tickets/create.php - Updated breadcrumbs
Create ticket template
-->
<?php
// Set page title
$pageTitle = 'Create Ticket for ' . $deliverable['name'];

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row mb-3">
    <!-- Breadcrumbs -->
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/projects.php">Projects</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $projectId; ?>"><?php echo htmlspecialchars($deliverable['project_name']); ?></a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/deliverables.php?id=<?php echo $deliverableId; ?>"><?php echo htmlspecialchars($deliverable['name']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Create Ticket</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-8" style="margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <h2>Create New Ticket</h2>
                <p class="text-muted">for deliverable: <?php echo htmlspecialchars($deliverable['name']); ?></p>
            </div>
            <div class="card-body">
                <form id="createTicketForm" action="<?php echo BASE_URL; ?>/api/tickets/create.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="deliverable_id" value="<?php echo $deliverableId; ?>">
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" class="form-control" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="url">URL (optional)</label>
                        <input type="url" id="url" name="url" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority" class="form-control" required>
                            <option value="1-Critical">1-Critical</option>
                            <option value="1-Important">1-Important</option>
                            <option value="2-Nice to have" selected>2-Nice to have</option>
                            <option value="3-Feature Request">3-Feature Request</option>
                            <option value="4-Nice to have">4-Nice to have</option>
                        </select>
                    </div>
                    
                    <?php if ($userRole !== 'Tester'): ?>
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status" class="form-control" required>
                            <option value="New" selected>New</option>
                            <option value="Needs clarification">Needs clarification</option>
                            <option value="Assigned">Assigned</option>
                            <option value="In progress">In progress</option>
                            <option value="In review">In review</option>
                            <option value="Complete">Complete</option>
                            <option value="Rejected">Rejected</option>
                            <option value="Ignored">Ignored</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="assigned_to">Assign To (optional)</label>
                        <select id="assigned_to" name="assigned_to" class="form-control">
                            <option value="">Unassigned</option>
                            <?php
                            $projectUsers = getProjectUsers($projectId);
                            foreach ($projectUsers as $user):
                            ?>
                            <option value="<?php echo $user['user_id']; ?>"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> (<?php echo $user['role']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="status" value="New">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="files">Attach Files (optional)</label>
                        <input type="file" id="files" name="files[]" class="form-control" multiple>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Create Ticket</button>
                        <a href="<?php echo BASE_URL; ?>/deliverables.php?id=<?php echo $deliverableId; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Javascript for ticket creation -->
<script src="<?php echo BASE_URL; ?>/assets/js/tickets.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>