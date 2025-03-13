<?php
// views/deliverables/create.php
// Create deliverable template

// Set page title
$pageTitle = 'Create Deliverable for ' . $project['name'];

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
                <li class="breadcrumb-item active" aria-current="page">Create Deliverable</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-8" style="margin: 0 auto;">
        <div class="card">
            <div class="card-header">
                <h2>Create New Deliverable</h2>
                <p class="text-muted">for project: <?php echo htmlspecialchars($project['name']); ?></p>
            </div>
            <div class="card-body">
                <form id="createDeliverableForm" action="<?php echo BASE_URL; ?>/api/deliverables/create.php" method="POST">
                    <input type="hidden" name="project_id" id="project_id" value="<?php echo $project['project_id']; ?>">
                    
                    <div class="form-group">
                        <label for="name">Deliverable Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="5" class="form-control"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Create Deliverable</button>
                        <a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $project['project_id']; ?>" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Javascript for deliverable creation -->
<script src="<?php echo BASE_URL; ?>/assets/js/deliverables.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>