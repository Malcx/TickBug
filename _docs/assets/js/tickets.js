/**
 * assets/js/tickets.js
 * Ticket related JavaScript functionality
 */

$(document).ready(function() {
    // Create ticket form submission
    $("#createTicketForm").submit(function(event) {
        event.preventDefault();
        
        // Create FormData object for file uploads
        var formData = new FormData(this);
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: formData,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Redirect to view the new ticket
                    window.location.href = BASE_URL + "/tickets.php?id=" + response.ticket.ticket_id;
                } else {
                    showFormError($("#createTicketForm"), response.message);
                }
            },
            error: function(xhr, status, error) {
                console.error("AJAX Error:", status, error);
                if (xhr.responseText) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        showFormError($("#createTicketForm"), response.message || "Server error. Please try again.");
                    } catch(e) {
                        showFormError($("#createTicketForm"), "An error occurred: " + xhr.responseText);
                    }
                } else {
                    showFormError($("#createTicketForm"), "An error occurred. Please try again.");
                }
            }
        });
    });
    
    // Edit ticket form submission
    $("#editTicketForm").submit(function(event) {
        event.preventDefault();
        
        // Create FormData object for file uploads
        var formData = new FormData(this);
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: formData,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showFormSuccess($("#editTicketForm"), response.message);
                    
                    // Redirect back to view page after short delay
                    setTimeout(function() {
                        var ticketId = $("#editTicketForm input[name='ticket_id']").val();
                        window.location.href = BASE_URL + "/tickets.php?id=" + ticketId;
                    }, 1500);
                } else {
                    showFormError($("#editTicketForm"), response.message);
                }
            },
            error: function(message) {
                console.log(message)
                showFormError($("#editTicketForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Delete ticket button click
    $(".delete-ticket").click(function() {
        // Get ticket ID
        var ticketId = $(this).data("id");
        
        // Store ticket ID in modal for confirmation
        $("#confirmDelete").data("id", ticketId);
        
        // Show confirmation modal
        $("#deleteConfirmModal").modal("show");
    });
    
    // Delete confirmation click
    $("#confirmDelete").click(function() {
        var ticketId = $(this).data("id");
        
        // Submit delete request via AJAX
        $.ajax({
            url: BASE_URL + "/api/tickets/delete.php",
            type: "POST",
            data: {
                ticket_id: ticketId
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Redirect to project page
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
    
    // Change ticket status
    $(".change-status").click(function() {
        var ticketId = $(this).data("ticket");
        var statusId = $(this).data("status-id");
        
        // Submit status change via AJAX
        $.ajax({
            url: BASE_URL + "/api/tickets/change-status.php",
            type: "POST",
            data: {
                ticket_id: ticketId,
                status_id: statusId
            },
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Reload page to reflect changes
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function(response) {
                alert("An error occurred. Please try again."); 
                console.log(response)
            }
        });
    });    
    // Assign ticket form submission
    $("#assignForm").submit(function(event) {
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
                    alert("Ticket assignment updated successfully.");
                    
                    // Reload page to reflect changes
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert("An error occurred. Please try again.");
            }
        });
    });
    
    // Add comment form submission
    $("#addCommentForm").submit(function(event) {
        event.preventDefault();
        
        // Create FormData object for file uploads
        var formData = new FormData(this);
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: formData,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Reload page to show new comment
                    location.reload();
                } else {
                    showFormError($("#addCommentForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#addCommentForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Edit comment button click
    $(document).on("click", ".edit-comment", function() {
        var commentId = $(this).data("id");
        var commentCard = $(this).closest(".comment-card");
        var commentContent = commentCard.find(".comment-content").html();
        var commentDescription = commentCard.find(".comment-content").text().trim();
        
        // Find URL in comment if it exists
        var commentUrl = "";
        var urlMatch = commentContent.match(/<a href="([^"]+)"[^>]*>/);
        if (urlMatch && urlMatch.length > 1) {
            commentUrl = urlMatch[1];
        }
        
        // Create edit form
        var editForm = $('<form id="editCommentForm" action="' + BASE_URL + '/api/comments/update.php" method="POST" enctype="multipart/form-data">' +
            '<input type="hidden" name="comment_id" value="' + commentId + '">' +
            '<div class="form-group">' +
            '<label for="description">Comment</label>' +
            '<textarea id="description" name="description" rows="3" class="form-control" required>' + commentDescription + '</textarea>' +
            '</div>' +
            '<div class="form-group">' +
            '<label for="url">URL (optional)</label>' +
            '<input type="url" id="url" name="url" class="form-control" value="' + commentUrl + '">' +
            '</div>' +
            '<div class="form-group">' +
            '<label for="files">Add More Files (optional)</label>' +
            '<input type="file" id="files" name="files[]" class="form-control" multiple>' +
            '</div>' +
            '<button type="submit" class="btn btn-primary">Update Comment</button> ' +
            '<button type="button" class="btn btn-secondary cancel-edit">Cancel</button>' +
            '</form>');
        
        // Replace comment content with edit form
        commentCard.find(".card-body").html(editForm);
    });
    
    // Cancel comment edit
    $(document).on("click", ".cancel-edit", function() {
        // Reload page to reset
        location.reload();
    });
    
    // Edit comment form submission
    $(document).on("submit", "#editCommentForm", function(event) {
        event.preventDefault();
        
        // Create FormData object for file uploads
        var formData = new FormData(this);
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: formData,
            dataType: "json",
            contentType: false,
            processData: false,
            success: function(response) {
                if (response.success) {
                    // Reload page to show updated comment
                    location.reload();
                } else {
                    alert(response.message);
                }
            },
            error: function() {
                alert("An error occurred. Please try again.");
            }
        });
    });
    
    // Delete comment button click
    $(document).on("click", ".delete-comment", function() {
        if (confirm("Are you sure you want to delete this comment?")) {
            var commentId = $(this).data("id");
            
            // Submit delete request via AJAX
            $.ajax({
                url: BASE_URL + "/api/comments/delete.php",
                type: "POST",
                data: {
                    comment_id: commentId
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Reload page to reflect changes
                        location.reload();
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert("An error occurred. Please try again.");
                }
            });
        }
    });
    
    // Delete file button click
    $(document).on("click", ".delete-file", function() {
        if (confirm("Are you sure you want to delete this file?")) {
            var fileId = $(this).data("id");
            var fileCard = $(this).closest(".file-card");
            
            // Submit delete request via AJAX
            $.ajax({
                url: BASE_URL + "/api/files/delete.php",
                type: "POST",
                data: {
                    file_id: fileId
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Remove file card from UI
                        fileCard.fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.message);
                    }
                },
                error: function() {
                    alert("An error occurred. Please try again.");
                }
            });
        }
    });
    
    // Filter tickets by status
    $("#filter-status").change(function() {
        filterTickets();
    });
    
    // Filter tickets by priority
    $("#filter-priority").change(function() {
        filterTickets();
    });
    
    // Filter tickets by assignee
    $("#filter-assignee").change(function() {
        filterTickets();
    });
    
    // Search tickets
    $("#ticket-search").on("input", function() {
        filterTickets();
    });
    
    // Initialize filters if present on page
    if ($("#filter-status").length) {
        initializeFilters();
    }
});

/**
 * Initialize ticket filters
 */
function initializeFilters() {
    // Get filter values from URL if present
    var urlParams = new URLSearchParams(window.location.search);
    
    if (urlParams.has('status')) {
        $("#filter-status").val(urlParams.get('status'));
    }
    
    if (urlParams.has('priority')) {
        $("#filter-priority").val(urlParams.get('priority'));
    }
    
    if (urlParams.has('assignee')) {
        $("#filter-assignee").val(urlParams.get('assignee'));
    }
    
    if (urlParams.has('search')) {
        $("#ticket-search").val(urlParams.get('search'));
    }
    
    // Apply filters
    filterTickets();
}

/**
 * Filter tickets based on selected criteria
 */
function filterTickets() {
    var status = $("#filter-status").val();
    var priority = $("#filter-priority").val();
    var assignee = $("#filter-assignee").val();
    var search = $("#ticket-search").val().toLowerCase();
    
    $(".ticket-card").each(function() {
        var ticketStatus = $(this).data("status-id");
        var ticketPriorityId = $(this).data("priority-id");
        var ticketAssignee = $(this).data("assignee");
        var ticketTitle = $(this).find("h5 a").text().toLowerCase();
        
        var statusMatch = status === "" || ticketStatus === status;
        var priorityMatch = priority === "" || ticketPriority === priority;
        var assigneeMatch = assignee === "" || ticketAssignee === assignee || (assignee === "unassigned" && !ticketAssignee);
        var searchMatch = search === "" || ticketTitle.indexOf(search) > -1;
        
        if (statusMatch && priorityMatch && assigneeMatch && searchMatch) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    
    // Update URL with filter parameters without refreshing page
    var url = new URL(window.location.href);
    
    if (status) {
        url.searchParams.set('status', status);
    } else {
        url.searchParams.delete('status');
    }
    
    if (priority) {
        url.searchParams.set('priority', priority);
    } else {
        url.searchParams.delete('priority');
    }
    
    if (assignee) {
        url.searchParams.set('assignee', assignee);
    } else {
        url.searchParams.delete('assignee');
    }
    
    if (search) {
        url.searchParams.set('search', search);
    } else {
        url.searchParams.delete('search');
    }
    
    window.history.replaceState({}, '', url);
}

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