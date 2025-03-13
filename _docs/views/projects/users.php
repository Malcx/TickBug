<?php
// views/projects/users.php
// Manage project users template

// Set page title
$pageTitle = 'Manage Users: ' . $project['name'];

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
                <li class="breadcrumb-item active" aria-current="page">Manage Users</li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h2>Manage Project Users</h2>
                <p class="text-muted">Add users to the project and manage their roles</p>
            </div>
            <div class="card-body">
                <!-- Add User Form -->
                <div class="mb-4">
                    <h3>Add User</h3>
                    <form id="addUserForm" method="POST">
                        <input type="hidden" name="project_id" id="project_id" value="<?php echo $project['project_id']; ?>">
                        
                        <div class="row">
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="email">User Email</label>
                                    <input type="email" id="email" name="email" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-5">
                                <div class="form-group">
                                    <label for="role">Role</label>
                                    <select id="role" name="role" class="form-control" required>
                                        <?php if ($userRole === 'Owner'): ?>
                                            <option value="Owner">Owner</option>
                                        <?php endif; ?>
                                        <option value="Project Manager">Project Manager</option>
                                        <option value="Developer">Developer</option>
                                        <option value="Designer">Designer</option>
                                        <option value="Reviewer">Reviewer</option>
                                        <option value="Tester">Tester</option>
                                        <option value="Viewer">Viewer</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>&nbsp;</label>
                                    <button type="submit" class="btn btn-primary btn-block">Add User</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Current Users Table -->
                <h3>Current Users</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projectUsers as $pUser): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($pUser['first_name'] . ' ' . $pUser['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($pUser['email']); ?></td>
                                    <td>
                                        <?php if (($userRole === 'Owner' || ($userRole === 'Project Manager' && $pUser['role'] !== 'Owner')) && $pUser['user_id'] !== $userId): ?>
                                            <select class="form-control change-role" data-user="<?php echo $pUser['user_id']; ?>">
                                                <?php if ($userRole === 'Owner'): ?>
                                                    <option value="Owner" <?php echo ($pUser['role'] === 'Owner') ? 'selected' : ''; ?>>Owner</option>
                                                <?php endif; ?>
                                                <option value="Project Manager" <?php echo ($pUser['role'] === 'Project Manager') ? 'selected' : ''; ?>>Project Manager</option>
                                                <option value="Developer" <?php echo ($pUser['role'] === 'Developer') ? 'selected' : ''; ?>>Developer</option>
                                                <option value="Designer" <?php echo ($pUser['role'] === 'Designer') ? 'selected' : ''; ?>>Designer</option>
                                                <option value="Reviewer" <?php echo ($pUser['role'] === 'Reviewer') ? 'selected' : ''; ?>>Reviewer</option>
                                                <option value="Tester" <?php echo ($pUser['role'] === 'Tester') ? 'selected' : ''; ?>>Tester</option>
                                                <option value="Viewer" <?php echo ($pUser['role'] === 'Viewer') ? 'selected' : ''; ?>>Viewer</option>
                                            </select>
                                        <?php else: ?>
                                            <?php echo $pUser['role']; ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($userRole === 'Owner' || ($userRole === 'Project Manager' && $pUser['role'] !== 'Owner')) && $pUser['user_id'] !== $userId): ?>
                                            <button class="btn btn-danger btn-sm remove-user" data-user="<?php echo $pUser['user_id']; ?>">Remove</button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- User Role Information -->
                <div class="mt-4">
                    <h3>Role Information</h3>
                    <div class="card">
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-md-3">Owner</dt>
                                <dd class="col-md-9">Full access to the project. Can add/remove users, manage all aspects of the project, and archive/delete the project.</dd>
                                
                                <dt class="col-md-3">Project Manager</dt>
                                <dd class="col-md-9">Can manage deliverables, tickets, and users but cannot add or remove owners or archive/delete the project.</dd>
                                
                                <dt class="col-md-3">Developer</dt>
                                <dd class="col-md-9">Can create, edit, and update tickets and work on assigned items.</dd>
                                
                                <dt class="col-md-3">Designer</dt>
                                <dd class="col-md-9">Can create, edit, and update tickets and work on assigned items.</dd>
                                
                                <dt class="col-md-3">Reviewer</dt>
                                <dd class="col-md-9">Can view and comment on tickets, and has access to reports.</dd>
                                
                                <dt class="col-md-3">Tester</dt>
                                <dd class="col-md-9">Can create tickets and add comments but cannot edit existing tickets unless they created them.</dd>
                                
                                <dt class="col-md-3">Viewer</dt>
                                <dd class="col-md-9">Read-only access to all project contents.</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="<?php echo BASE_URL; ?>/projects.php?id=<?php echo $project['project_id']; ?>" class="btn btn-secondary">Back to Project</a>
            </div>
        </div>
    </div>
</div>

<!-- Javascript for project users -->
<script src="<?php echo BASE_URL; ?>/assets/js/projects.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>