<!--
views/tickets/view.php
View ticket details template
-->
<?php
// Set page title
$pageTitle = $ticket['title'];

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
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($ticket['title']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<div class="row">
    <!-- Main ticket content -->
    <div class="col-8">
        <div class="card mb-3">
            <div class="card-header">
                <div class="row">
                    <div class="col-8">
                        <h1><?php echo htmlspecialchars($ticket['title']); ?></h1>
                    </div>
                    <div class="col-4 text-right">
                        <?php if ($userRole !== 'Viewer' && ($userRole !== 'Tester' || $ticket['created_by'] === $userId)): ?>
                            <a href="<?php echo BASE_URL; ?>/tickets.php?action=edit&id=<?php echo $ticket['ticket_id']; ?>" class="btn btn-secondary">Edit Ticket</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Ticket details -->
                <div class="row mb-3">
                    <div class="col-4">
                        <p><strong>Status:</strong> <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $ticket['status_name'])); ?>"><?php echo $ticket['status_name']; ?></span></p>
                    </div>
                    <div class="col-4">
                        <p><strong>Priority:</strong> <span class="badge badge-priority-<?php echo strtolower(str_replace(' ', '-', $ticket['priority_name'])); ?>"><?php echo $ticket['priority_name']; ?></span></p>
                    </div>
                    <div class="col-4">
                        <p><strong>Created by:</strong> <?php echo htmlspecialchars($ticket['creator_first_name'] . ' ' . $ticket['creator_last_name']); ?></p>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-4">
                        <p><strong>Created on:</strong> <?php echo formatDate($ticket['created_at']); ?></p>
                    </div>
                    <div class="col-4">
                        <p><strong>Updated on:</strong> <?php echo formatDate($ticket['updated_at']); ?></p>
                    </div>
                    <div class="col-4">
                        <p>
                            <strong>Assigned to:</strong> 
                            <?php if ($ticket['assigned_to']): ?>
                                <?php echo htmlspecialchars($ticket['assigned_name']); ?>
                            <?php else: ?>
                                <em>Unassigned</em>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
                
                <!-- Ticket description -->
                <div class="ticket-description mb-3">
                    <h4>Description</h4>
                    <div class="card">
                        <div class="card-body">
                            <?php echo nl2br(htmlspecialchars($ticket['description'])); ?>
                            
                            <?php if (!empty($ticket['url'])): ?>
                                <p class="mt-3">
                                    <strong>URL:</strong> <a href="<?php echo htmlspecialchars($ticket['url']); ?>" target="_blank"><?php echo htmlspecialchars($ticket['url']); ?></a>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Ticket files -->
                <?php if (!empty($ticket['files'])): ?>
                <div class="ticket-files mb-3">
                    <h4>Files</h4>
                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($ticket['files'] as $file): ?>
                                <div class="col-3 mb-3">
                                    <div class="file-card">
                                        <div class="file-icon file-<?php echo getFileIconClass($file['filetype']); ?>"></div>
                                        <div class="file-info">
                                            <p class="file-name"><?php echo htmlspecialchars($file['filename']); ?></p>
                                            <p class="file-size"><?php echo formatFileSize($file['filesize']); ?></p>
                                            <a href="<?php echo BASE_URL; ?>/uploads.php?id=<?php echo $file['file_id']; ?>" class="btn btn-sm btn-primary" target="_blank">View</a>
                                            <?php if ($userRole !== 'Viewer' && ($file['uploaded_by'] === $userId || $userRole === 'Owner' || $userRole === 'Project Manager')): ?>
                                            <button class="btn btn-sm btn-danger delete-file" data-id="<?php echo $file['file_id']; ?>">Delete</button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Comments section -->
                <div class="ticket-comments">
                    <h4>Comments</h4>
                    
                    <!-- Add comment form -->
                    <?php if ($userRole !== 'Viewer'): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <form id="addCommentForm" action="<?php echo BASE_URL; ?>/api/comments/create.php" method="POST" enctype="multipart/form-data">
                                <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
                                
                                <div class="form-group">
                                    <label for="description">Comment</label>
                                    <textarea id="description" name="description" rows="3" class="form-control" required></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label for="url">URL (optional)</label>
                                    <input type="url" id="url" name="url" class="form-control">
                                </div>
                                
                                <div class="form-group">
                                    <label for="files">Attach Files (optional)</label>
                                    <input type="file" id="files" name="files[]" class="form-control" multiple>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">Add Comment</button>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Comments list -->
                    <div id="comments-container">
                        <?php if (empty($ticket['comments'])): ?>
                            <div class="alert alert-info">No comments yet.</div>
                        <?php else: ?>
                            <?php foreach ($ticket['comments'] as $comment): ?>
                                <div class="card mb-3 comment-card" data-id="<?php echo $comment['comment_id']; ?>">
                                    <div class="card-header">
                                        <div class="row">
                                            <div class="col-8">
                                                <strong><?php echo htmlspecialchars($comment['first_name'] . ' ' . $comment['last_name']); ?></strong>
                                                <span class="text-muted ml-2"><?php echo formatDate($comment['created_at']); ?></span>
                                            </div>
                                            <div class="col-4 text-right">
                                                <?php if ($userRole !== 'Viewer' && ($comment['user_id'] === $userId || $userRole === 'Owner' || $userRole === 'Project Manager')): ?>
                                                <button class="btn btn-sm btn-secondary edit-comment" data-id="<?php echo $comment['comment_id']; ?>">Edit</button>
                                                <button class="btn btn-sm btn-danger delete-comment" data-id="<?php echo $comment['comment_id']; ?>">Delete</button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="comment-content">
                                            <?php echo nl2br(htmlspecialchars($comment['description'])); ?>
                                            
                                            <?php if (!empty($comment['url'])): ?>
                                                <p class="mt-3">
                                                    <strong>URL:</strong> <a href="<?php echo htmlspecialchars($comment['url']); ?>" target="_blank"><?php echo htmlspecialchars($comment['url']); ?></a>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <!-- Comment files -->
                                        <?php if (!empty($comment['files'])): ?>
                                        <div class="comment-files mt-3">
                                            <h5>Files</h5>
                                            <div class="row">
                                                <?php foreach ($comment['files'] as $file): ?>
                                                <div class="col-3 mb-3">
                                                    <div class="file-card">
                                                        <div class="file-icon file-<?php echo getFileIconClass($file['filetype']); ?>"></div>
                                                        <div class="file-info">
                                                            <p class="file-name"><?php echo htmlspecialchars($file['filename']); ?></p>
                                                            <p class="file-size"><?php echo formatFileSize($file['filesize']); ?></p>
                                                            <a href="<?php echo BASE_URL; ?>/uploads.php?id=<?php echo $file['file_id']; ?>" class="btn btn-sm btn-primary" target="_blank">View</a>
                                                            <?php if ($userRole !== 'Viewer' && ($file['uploaded_by'] === $userId || $userRole === 'Owner' || $userRole === 'Project Manager')): ?>
                                                            <button class="btn btn-sm btn-danger delete-file" data-id="<?php echo $file['file_id']; ?>">Delete</button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Sidebar -->
    <div class="col-4">
        <!-- Quick actions -->
        <div class="card mb-3">
            <div class="card-header">
                <h3>Actions</h3>
            </div>
            <div class="card-body">
                <?php if ($userRole !== 'Viewer' && $userRole !== 'Tester'): ?>
                    <div class="mb-3">
                        <h4>Change Status</h4>
                        <div class="status-buttons">
                            <?php
                            $statuses = [
                                1 => 'New',
                                2 => 'Needs clarification',
                                3 => 'Assigned',
                                4 => 'In progress',
                                5 => 'In review',
                                6 => 'Complete',
                                7 => 'Rejected',
                                8 => 'Ignored'
                            ];
                            foreach ($statuses as $statusId => $statusName):
                                $statusClass = strtolower(str_replace(' ', '-', $statusName));
                                $disabled = ($statusId == $ticket['status_id']) ? 'disabled' : '';
                            ?>
                            <button class="btn btn-sm mb-2 badge-<?php echo $statusClass; ?> change-status" data-status-id="<?php echo $statusId; ?>" data-ticket="<?php echo $ticket['ticket_id']; ?>" <?php echo $disabled; ?>><?php echo $statusName; ?></button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                
                    <div class="mb-3">
                        <h4>Assign To</h4>
                        <form id="assignForm" action="<?php echo BASE_URL; ?>/api/tickets/assign.php" method="POST">
                            <input type="hidden" name="ticket_id" value="<?php echo $ticket['ticket_id']; ?>">
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
                            <button type="submit" class="btn btn-primary mt-2">Update Assignment</button>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <h4>Status</h4>
                        <span class="badge badge-<?php echo strtolower(str_replace(' ', '-', $ticket['status_name'])); ?>"><?php echo $ticket['status_name']; ?></span>
                    </div>
                    
                    <div class="mb-3">
                        <h4>Assigned To</h4>
                        <?php if ($ticket['assigned_to']): ?>
                            <p><?php echo htmlspecialchars($ticket['assigned_name']); ?></p>
                        <?php else: ?>
                            <p><em>Unassigned</em></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Activity log -->
        <div class="card">
            <div class="card-header">
                <h3>Activity Log</h3>
            </div>
            <div class="card-body">
                <?php
                // Get activity log for this ticket
                $conn = getDbConnection();
                $stmt = $conn->prepare("
                    SELECT a.*, u.first_name, u.last_name
                    FROM activity_log a
                    JOIN users u ON a.user_id = u.user_id
                    WHERE a.target_type = 'ticket' AND a.target_id = ?
                    ORDER BY a.created_at DESC
                    LIMIT 10
                ");
                $stmt->bind_param("i", $ticket['ticket_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0):
                ?>
                    <div class="alert alert-info">No activity recorded yet.</div>
                <?php else: ?>
                    <div class="activity-timeline">
                        <?php while ($activity = $result->fetch_assoc()): ?>
                            <?php
                            // Convert JSON details to array
                            $activity['details'] = json_decode($activity['details'], true);
                            
                            // Format activity description
                            $description = formatActivityDescription($activity);
                            $formattedTime = formatDate($activity['created_at']);
                            ?>
                            <div class="activity-item">
                                <div class="activity-time"><?php echo $formattedTime; ?></div>
                                <div class="activity-content">
                                    <p><?php echo $description; ?></p>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Javascript for ticket view page -->
<script src="<?php echo BASE_URL; ?>/assets/js/tickets.js"></script>

<?php
// Include footer
require_once ROOT_PATH . '/views/includes/footer.php';
?>