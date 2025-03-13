<?php
// views/users/profile.php
// User profile template

// Include header
require_once ROOT_PATH . '/views/includes/header.php';
?>

<div class="row mb-4">
    <div class="col-12">
        <h1>My Profile</h1>
    </div>
</div>

<div class="row">
    <!-- Profile Information -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Profile Information</h3>
            </div>
            <div class="card-body">
                <form id="updateProfileForm" action="<?php echo BASE_URL; ?>/api/auth/update-profile.php" method="POST">
                    <div class="form-group">
                        <label for="firstName">First Name</label>
                        <input type="text" id="firstName" name="first_name" class="form-control" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="lastName">Last Name</label>
                        <input type="text" id="lastName" name="last_name" class="form-control" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    
                    <hr>
                    
                    <h4>Change Password</h4>
                    <p class="text-muted">Leave blank to keep current password</p>
                    
                    <div class="form-group">
                        <label for="currentPassword">Current Password</label>
                        <div class="input-group">
                            <input type="password" id="currentPassword" name="current_password" class="form-control">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-toggle="#currentPassword">Show</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <div class="input-group">
                            <input type="password" id="newPassword" name="new_password" class="form-control">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-toggle="#newPassword">Show</button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Minimum 8 characters</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <div class="input-group">
                            <input type="password" id="confirmPassword" name="confirm_password" class="form-control">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-secondary toggle-password" data-toggle="#confirmPassword">Show</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Profile</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Notification Preferences -->
    <div class="col-md-6">
        <div class="card mb-4">
            <div class="card-header">
                <h3>Notification Preferences</h3>
            </div>
            <div class="card-body">
                <?php if (empty($notificationSettings)): ?>
                    <div class="alert alert-info">You are not currently part of any projects.</div>
                <?php else: ?>
                    <form id="notificationPreferencesForm" action="<?php echo BASE_URL; ?>/api/users/update-notifications.php" method="POST">
                        <?php foreach ($notificationSettings as $projectSettings): ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <h4><?php echo htmlspecialchars($projectSettings['project_name']); ?></h4>
                                </div>
                                <div class="card-body">
                                    <input type="hidden" name="project_ids[]" value="<?php echo $projectSettings['project_id']; ?>">
                                    
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="ticket_assigned_<?php echo $projectSettings['project_id']; ?>" name="preferences[<?php echo $projectSettings['project_id']; ?>][ticket_assigned]" <?php echo ($projectSettings['preferences']['ticket_assigned']) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="ticket_assigned_<?php echo $projectSettings['project_id']; ?>">When a ticket is assigned to me</label>
                                    </div>
                                    
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="ticket_status_changed_<?php echo $projectSettings['project_id']; ?>" name="preferences[<?php echo $projectSettings['project_id']; ?>][ticket_status_changed]" <?php echo ($projectSettings['preferences']['ticket_status_changed']) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="ticket_status_changed_<?php echo $projectSettings['project_id']; ?>">When a ticket status changes</label>
                                    </div>
                                    
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="ticket_commented_<?php echo $projectSettings['project_id']; ?>" name="preferences[<?php echo $projectSettings['project_id']; ?>][ticket_commented]" <?php echo ($projectSettings['preferences']['ticket_commented']) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="ticket_commented_<?php echo $projectSettings['project_id']; ?>">When someone comments on my ticket</label>
                                    </div>
                                    
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="deliverable_created_<?php echo $projectSettings['project_id']; ?>" name="preferences[<?php echo $projectSettings['project_id']; ?>][deliverable_created]" <?php echo ($projectSettings['preferences']['deliverable_created']) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="deliverable_created_<?php echo $projectSettings['project_id']; ?>">When a new deliverable is created</label>
                                    </div>
                                    
                                    <div class="custom-control custom-switch mb-2">
                                        <input type="checkbox" class="custom-control-input" id="project_user_added_<?php echo $projectSettings['project_id']; ?>" name="preferences[<?php echo $projectSettings['project_id']; ?>][project_user_added]" <?php echo ($projectSettings['preferences']['project_user_added']) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="project_user_added_<?php echo $projectSettings['project_id']; ?>">When a user is added to the project</label>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Save Preferences</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- User Stats -->
        <div class="card">
            <div class="card-header">
                <h3>Activity Statistics</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-6 mb-3">
                        <div class="card text-center bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Projects</h5>
                                <p class="card-text display-4"><?php echo $stats['projects']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card text-center bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Created Tickets</h5>
                                <p class="card-text display-4"><?php echo $stats['tickets_created']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card text-center bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Assigned Tickets</h5>
                                <p class="card-text display-4"><?php echo $stats['tickets_assigned']; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-3">
                        <div class="card text-center bg-light">
                            <div class="card-body">
                                <h5 class="card-title">Completed Tickets</h5>
                                <p class="card-text display-4"><?php echo $stats['tickets_completed']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php if ($stats['tickets_assigned'] > 0): ?>
                    <div class="progress mt-3 mb-2">
                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo ($stats['tickets_completed'] / $stats['tickets_assigned']) * 100; ?>%" aria-valuenow="<?php echo ($stats['tickets_completed'] / $stats['tickets_assigned']) * 100; ?>" aria-valuemin="0" aria-valuemax="100"><?php echo round(($stats['tickets_completed'] / $stats['tickets_assigned']) * 100); ?>% Complete</div>
                    </div>
                <?php endif; ?>
                
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>/reports.php?type=user&user_id=<?php echo $userId; ?>" class="btn btn-info">View Detailed Report</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Javascript for user profile -->
<script src="<?php echo BASE_URL; ?>/assets/js/users.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>