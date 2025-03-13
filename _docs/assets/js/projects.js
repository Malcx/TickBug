/**
 * assets/js/projects.js
 * Project related JavaScript functionality
 */

$(document).ready(function() {
    // Create project form submission
    $("#createProjectForm").submit(function(event) {
        event.preventDefault();
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Redirect to projects page
                    window.location.href = BASE_URL + "/projects.php";
                } else {
                    showFormError($("#createProjectForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#createProjectForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Edit project form submission
    $("#editProjectForm").submit(function(event) {
        event.preventDefault();
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showFormSuccess($("#editProjectForm"), response.message);
                    
                    // Redirect back to project view after short delay
                    setTimeout(function() {
                        var projectId = $("#editProjectForm input[name='project_id']").val();
                        window.location.href = BASE_URL + "/projects.php?id=" + projectId;
                    }, 1500);
                } else {
                    showFormError($("#editProjectForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#editProjectForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Add user to project form submission
    $("#addUserForm").submit(function(event) {
        event.preventDefault();
        
        // Submit form via AJAX
        $.ajax({
            url: BASE_URL + "/api/projects/users.php",
            type: "POST",
            data: $(this).serialize() + "&action=add",
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Reload page to show new user
                    location.reload();
                } else {
                    showFormError($("#addUserForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#addUserForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Change user role
    $(".change-role").change(function() {
        var userId = $(this).data("user");
        var role = $(this).val();
        var projectId = $("#project_id").val();
        
        // Submit role change via AJAX
        $.ajax({
            url: BASE_URL + "/api/projects/users.php",
            type: "POST",
            data: {
                action: "change_role",
                project_id: projectId,
                user_id: userId,
                role: role
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showNotification("success", "User role updated successfully.");
                } else {
                    showNotification("error", response.message);
                    // Reset select to previous value
                    location.reload();
                }
            },
            error: function() {
                showNotification("error", "An error occurred. Please try again.");
                // Reset select to previous value
                location.reload();
            }
        });
    });
    
    // Remove user from project
    $(".remove-user").click(function() {
        if (confirm("Are you sure you want to remove this user from the project?")) {
            var userId = $(this).data("user");
            var projectId = $("#project_id").val();
            
            // Submit remove request via AJAX
            $.ajax({
                url: BASE_URL + "/api/projects/users.php",
                type: "POST",
                data: {
                    action: "remove",
                    project_id: projectId,
                    user_id: userId
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Reload page to reflect changes
                        location.reload();
                    } else {
                        showNotification("error", response.message);
                    }
                },
                error: function() {
                    showNotification("error", "An error occurred. Please try again.");
                }
            });
        }
    });
    
    // Archive project
    $(".archive-project").click(function() {
        if (confirm("Are you sure you want to archive this project?")) {
            var projectId = $(this).data("id");
            
            // Submit archive request via AJAX
            $.ajax({
                url: BASE_URL + "/api/projects/archive.php",
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
                        showNotification("error", response.message);
                    }
                },
                error: function() {
                    showNotification("error", "An error occurred. Please try again.");
                }
            });
        }
    });
    
    // Unarchive project
    $(".unarchive-project").click(function() {
        if (confirm("Are you sure you want to unarchive this project?")) {
            var projectId = $(this).data("id");
            
            // Submit unarchive request via AJAX
            $.ajax({
                url: BASE_URL + "/api/projects/unarchive.php",
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
                        showNotification("error", response.message);
                    }
                },
                error: function() {
                    showNotification("error", "An error occurred. Please try again.");
                }
            });
        }
    });
    
    // Toggle filter on projects list
    $("#filter-toggle").click(function() {
        $("#filter-form").slideToggle();
    });
});

/**
 * Show form error message
 * 
 * @param {jQuery} form Form element
 * @param {string} message Error message
 */
function showFormError(form, message) {
    // Remove existing error messages
    form.find(".form-error").remove();
    
    // Add new error message
    form.prepend('<div class="alert alert-danger form-error">' + message + '</div>');
    
    // Scroll to error message
    $('html, body').animate({
        scrollTop: form.offset().top - 20
    }, 300);
}

/**
 * Show form success message
 * 
 * @param {jQuery} form Form element
 * @param {string} message Success message
 */
function showFormSuccess(form, message) {
    // Remove existing messages
    form.find(".form-error, .form-success").remove();
    
    // Add new success message
    form.prepend('<div class="alert alert-success form-success">' + message + '</div>');
    
    // Scroll to success message
    $('html, body').animate({
        scrollTop: form.offset().top - 20
    }, 300);
}

/**
 * Show notification
 * 
 * @param {string} type Notification type (success, error)
 * @param {string} message Notification message
 */
function showNotification(type, message) {
    // Check if notification container exists
    let container = $("#notification-container");
    if (container.length === 0) {
        // Create container if it doesn't exist
        $("body").append('<div id="notification-container"></div>');
        container = $("#notification-container");
    }
    
    // Create notification element
    const notification = $('<div class="notification notification-' + type + '">' + message + '</div>');
    container.append(notification);
    
    // Show notification with animation
    setTimeout(function() {
        notification.addClass("show");
    }, 10);
    
    // Remove notification after delay
    setTimeout(function() {
        notification.removeClass("show");
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 3000);
}