<!--
views/tickets/edit.php
Edit ticket template
-->
<?php
// Set page title
$pageTitle = 'Edit Ticket: ' . $ticket['title'];

// Get project and deliverable for breadcrumbs
$deliverable = getDeliverable($ticket['deliverable_id']);
$project = getProject($deliverable['project_id']);

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row mb-3">
    <!-- Breadcrumbs -->
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/projects.php">Projects</a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $project['project_id']; ?>"><?php echo htmlspecialchars($project['name']); ?></a></li>
                <li class="breadcrumb-item"><a href="<?php echo BASE_URL; ?>/tickets.php?id=<?php echo $ticket['ticket_id']; ?>"><?php echo htmlspecialchars($ticket['title']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page">Edit</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-8" style="margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <h2>Edit Ticket</h2>
            </div>
            <div class="card-body">
                <form id="editTicketForm" action="<?php echo BASE_URL; ?>/api/tickets/update.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($ticket['title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" class="form-control" required><?php echo htmlspecialchars($ticket['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="url">URL (optional)</label>
                        <input type="url" id="url" name="url" class="form-control" value="<?php echo htmlspecialchars($ticket['url']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="priority_id">Priority</label>
                        <select id="priority_id" name="priority_id" class="form-control" required>
                            <option value="1" <?php echo ($ticket['priority_id'] == 1) ? 'selected' : ''; ?>>1 - Critical</option>
                            <option value="2" <?php echo ($ticket['priority_id'] == 2) ? 'selected' : ''; ?>>2 - Important</option>
                            <option value="3" <?php echo ($ticket['priority_id'] == 3) ? 'selected' : ''; ?>>3 - Nice to have</option>
                            <option value="4" <?php echo ($ticket['priority_id'] == 4) ? 'selected' : ''; ?>>4 - Feature request</option>
                            <option value="5" <?php echo ($ticket['priority_id'] == 5) ? 'selected' : ''; ?>>5 - Cosmetic</option>
                            <option value="6" <?php echo ($ticket['priority_id'] == 6) ? 'selected' : ''; ?>>6 - Not set</option>
                        </select>
                    </div>
                    
                    <?php if ($userRole !== 'Tester'): ?>
                    <div class="form-group">
                        <label for="status_id">Status</label>
                        <select id="status_id" name="status_id" class="form-control" required>
                            <option value="1" <?php echo ($ticket['status_id'] == 1) ? 'selected' : ''; ?>>New</option>
                            <option value="2" <?php echo ($ticket['status_id'] == 2) ? 'selected' : ''; ?>>Needs clarification</option>
                            <option value="3" <?php echo ($ticket['status_id'] == 3) ? 'selected' : ''; ?>>Assigned</option>
                            <option value="4" <?php echo ($ticket['status_id'] == 4) ? 'selected' : ''; ?>>In progress</option>
                            <option value="5" <?php echo ($ticket['status_id'] == 5) ? 'selected' : ''; ?>>In review</option>
                            <option value="6" <?php echo ($ticket['status_id'] == 6) ? 'selected' : ''; ?>>Complete</option>
                            <option value="7" <?php echo ($ticket['status_id'] == 7) ? 'selected' : ''; ?>>Rejected</option>
                            <option value="8" <?php echo ($ticket['status_id'] == 8) ? 'selected' : ''; ?>>Ignored</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="assigned_to">Assign To (optional)</label>
                        <select id="assigned_to" name="assigned_to" class="form-control">
                            <option value="">Unassigned</option>
                            <?php
                            $projectUsers = getProjectUsers($project['project_id']);
                            foreach ($projectUsers as $user):
                                $selected = ($ticket['assigned_to'] === $user['user_id']) ? 'selected' : '';
                            ?>
                            <option value="<?php echo $user['user_id']; ?>" <?php echo $selected; ?>><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?> (<?php echo $user['role']; ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php else: ?>
                    <input type="hidden" name="status" value="<?php echo $ticket['status']; ?>">
                    <input type="hidden" name="assigned_to" value="<?php echo $ticket['assigned_to']; ?>">
                    <?php endif; ?>
                    
                    <!-- Existing files -->
                    <?php if (!empty($ticket['files'])): ?>
                    <div class="form-group">
                        <label>Existing Files</label>
                        <div class="row">
                            <?php foreach ($ticket['files'] as $file): ?>
                            <div class="col-3 mb-3">
                                <div class="file-card">
                                    <div class="file-icon file-<?php echo getFileIconClass($file['filetype']); ?>"></div>
                                    <div class="file-info">
                                        <p class="file-name"><?php echo htmlspecialchars($file['filename']); ?></p>
                                        <p class="file-size"><?php echo formatFileSize($file['filesize']); ?></p>
                                        <a href="<?php echo BASE_URL; ?>/uploads.php?id=<?php echo $file['file_id']; ?>" class="btn btn-sm btn-primary" target="_blank">View</a>
                                        <button type="button" class="btn btn-sm btn-danger delete-file" data-id="<?php echo $file['file_id']; ?>">Delete</button>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="files">Add More Files (optional)</label>
                        <input type="file" id="files" name="files[]" class="form-control" multiple>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Ticket</button>
                        <a href="<?php echo BASE_URL; ?>/tickets.php?id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-secondary">Cancel</a>
                        
                        <?php if (canPerformAction('delete_ticket', $userRole) || $ticket['created_by'] === $userId): ?>
                        <button type="button" class="btn btn-danger float-right delete-ticket" data-id="<?php echo $ticket['ticket_id']; ?>">Delete Ticket</button>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Confirmation Modal for Delete -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this ticket? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Javascript for ticket editing -->
<script src="<?php echo BASE_URL; ?>/assets/js/tickets.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>