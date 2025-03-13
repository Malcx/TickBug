<?php
// views/projects/edit.php
// Edit project template

// Set page title
$pageTitle = 'Edit Project: ' . $project['name'];

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
                <li class="breadcrumb-item active" aria-current="page">Edit Project</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-8" style="margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <h2>Edit Project</h2>
            </div>
            <div class="card-body">
                <form id="editProjectForm" action="<?php echo BASE_URL; ?>/api/projects/update.php" method="POST">
                    <input type="hidden" name="project_id" value="<?php echo $project['project_id']; ?>">
                    
                    <div class="form-group">
                        <label for="name">Project Name</label>
                        <input type="text" id="name" name="name" class="form-control" value="<?php echo htmlspecialchars($project['name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" class="form-control"><?php echo htmlspecialchars($project['description']); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Project</button>
                        <a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $project['project_id']; ?>" class="btn btn-secondary">Cancel</a>
                        
                        <?php if ($userRole === 'Owner'): ?>
                            <?php if ($project['archived']): ?>
                                <button type="button" class="btn btn-success float-right unarchive-project" data-id="<?php echo $project['project_id']; ?>">Unarchive Project</button>
                            <?php else: ?>
                                <button type="button" class="btn btn-warning float-right archive-project" data-id="<?php echo $project['project_id']; ?>">Archive Project</button>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Project management links -->
        <div class="card mt-4">
            <div class="card-header">
                <h3>Project Management</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <a href="<?php echo BASE_URL; ?>/projects.php?action=users&id=<?php echo $project['project_id']; ?>" class="btn btn-primary btn-block mb-3">
                            <i class="fa fa-users"></i> Manage Users
                        </a>
                    </div>
                    
                    <div class="col-md-6">
                        <a href="<?php echo BASE_URL; ?>/reports.php?type=project&project_id=<?php echo $project['project_id']; ?>" class="btn btn-info btn-block mb-3">
                            <i class="fa fa-chart-bar"></i> Project Reports
                        </a>
                    </div>
                </div>
                
                <?php if ($userRole === 'Owner'): ?>
                <div class="alert alert-warning mt-3">
                    <h4>Danger Zone</h4>
                    <p>The following actions cannot be undone. Be careful!</p>
                    
                    <button type="button" class="btn btn-danger delete-project" data-id="<?php echo $project['project_id']; ?>">Delete Project</button>
                </div>
                <?php endif; ?>
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
                <p>Are you sure you want to delete this project? This will permanently delete:</p>
                <ul>
                    <li>All deliverables</li>
                    <li>All tickets</li>
                    <li>All comments</li>
                    <li>All files</li>
                    <li>All user assignments</li>
                </ul>
                <p><strong>This action cannot be undone.</strong></p>
                <div class="form-group">
                    <label for="confirmProjectName">Type the project name to confirm: <strong><?php echo htmlspecialchars($project['name']); ?></strong></label>
                    <input type="text" class="form-control" id="confirmProjectName">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete" disabled>Delete Project</button>
            </div>
        </div>
    </div>
</div>

<!-- Javascript for project editing -->
<script src="<?php echo BASE_URL; ?>/assets/js/projects.js"></script>

<script>
// Script for the delete confirmation
$(document).ready(function() {
    // Delete project button click
    $(".delete-project").click(function() {
        $("#deleteConfirmModal").modal("show");
    });
    
    // Enable delete button only if project name matches
    $("#confirmProjectName").on("input", function() {
        var projectName = "<?php echo addslashes($project['name']); ?>";
        $("#confirmDelete").prop("disabled", $(this).val() !== projectName);
    });
    
    // Delete confirmation click
    $("#confirmDelete").click(function() {
        var projectId = <?php echo $project['project_id']; ?>;
        
        // Submit delete request via AJAX
        $.ajax({
            url: BASE_URL + "/api/projects/delete.php",
            type: "POST",
            data: {
                project_id: projectId
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Redirect to projects page
                    window.location.href = BASE_URL + "/projects.php";
                } else {
                    // Hide modal
                    $("#deleteConfirmModal").modal("hide");
                    
                    // Show error
                    alert(response.message);
                }
            },
            error: function() {
                // Hide modal
                $("#deleteConfirmModal").modal("hide");
                
                // Show error
                alert("An error occurred. Please try again.");
            }
        });
    });
});
</script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>