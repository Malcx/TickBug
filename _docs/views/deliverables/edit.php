<?php
// views/deliverables/edit.php
// Edit deliverable template

// Set page title
$pageTitle = 'Edit Deliverable: ' . $deliverable['name'];

// Get project for breadcrumbs
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
                <li class="breadcrumb-item active" aria-current="page">Edit Deliverable</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-8" style="margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <h2>Edit Deliverable</h2>
            </div>
            <div class="card-body">
                <form id="editDeliverableForm" action="<?php echo BASE_URL; ?>/api/deliverables/update.php" method="POST" data-deliverable="<?php echo $deliverable['deliverable_id']; ?>">
                    <input type="hidden" name="deliverable_id" value="<?php echo $deliverable['deliverable_id']; ?>">
                    <input type="hidden" name="project_id" id="project_id" value="<?php echo $deliverable['project_id']; ?>">
                    
                    <div class="form-group">
                        <label for="name">Deliverable Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($deliverable['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" class="form-control"><?php echo htmlspecialchars($deliverable['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Deliverable</button>
                        <a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $deliverable['project_id']; ?>" class="btn btn-secondary">Cancel</a>
                        
                        <?php if (canPerformAction('delete_deliverable', $userRole)): ?>
                        <button type="button" class="btn btn-danger float-right delete-deliverable" data-id="<?php echo $deliverable['deliverable_id']; ?>">Delete Deliverable</button>
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
                Are you sure you want to delete this deliverable? This will also delete all tickets within it. This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Javascript for deliverable editing -->
<script src="<?php echo BASE_URL; ?>/assets/js/deliverables.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>