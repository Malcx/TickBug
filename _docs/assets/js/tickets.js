/**
 * assets/js/tickets.js
 * Ticket related JavaScript functionality
 * Uses utils.js for common functions
 */

$(document).ready(function() {
    // Create ticket form submission
    $("#createTicketForm").submit(function(event) {
        event.preventDefault();
        submitForm({
            form: $(this),
            hasFiles: true,
            onSuccess: function(response) {
                window.location.href = BASE_URL + "/tickets.php?id=" + response.ticket.ticket_id;
            }
        });
    });

    // Edit ticket form submission
    $("#editTicketForm").submit(function(event) {
        event.preventDefault();
        var ticketId = $("#editTicketForm input[name='ticket_id']").val();
        submitForm({
            form: $(this),
            hasFiles: true,
            showSuccessMessage: true,
            successRedirect: BASE_URL + "/tickets.php?id=" + ticketId,
            redirectDelay: 1500
        });
    });

    // Delete ticket button click
    $(".delete-ticket").click(function() {
        var ticketId = $(this).data("id");
        $("#confirmDelete").data("id", ticketId);
        $("#deleteConfirmModal").modal("show");
    });

    // Delete confirmation click
    $("#confirmDelete").click(function() {
        var ticketId = $(this).data("id");
        ajaxRequest({
            url: BASE_URL + "/api/tickets/delete.php",
            data: { ticket_id: ticketId },
            onSuccess: function() {
                window.location.href = BASE_URL + "/projects.php";
            },
            onError: function(message) {
                $("#deleteConfirmModal").modal("hide");
                alert(message);
            }
        });
    });

    // Change ticket status
    $(".change-status").click(function() {
        var ticketId = $(this).data("ticket");
        var statusId = $(this).data("status-id");
        ajaxRequest({
            url: BASE_URL + "/api/tickets/change-status.php",
            data: { ticket_id: ticketId, status_id: statusId },
            reloadOnSuccess: true,
            onError: function(message) {
                alert(message);
            }
        });
    });

    // Assign ticket form submission
    $("#assignForm").submit(function(event) {
        event.preventDefault();
        submitForm({
            form: $(this),
            onSuccess: function() {
                showNotification("success", "Ticket assignment updated successfully.");
                location.reload();
            }
        });
    });

    // Add comment form submission
    $("#addCommentForm").submit(function(event) {
        event.preventDefault();
        submitForm({
            form: $(this),
            hasFiles: true,
            reloadOnSuccess: true
        });
    });

    // Edit comment button click
    $(document).on("click", ".edit-comment", function() {
        var commentId = $(this).data("id");
        var commentCard = $(this).closest(".comment-card");
        var commentContent = commentCard.find(".comment-content").html();
        var commentDescription = commentCard.find(".comment-content").text().trim();

        var commentUrl = "";
        var urlMatch = commentContent.match(/<a href="([^"]+)"[^>]*>/);
        if (urlMatch && urlMatch.length > 1) {
            commentUrl = urlMatch[1];
        }

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

        commentCard.find(".card-body").html(editForm);
    });

    // Cancel comment edit
    $(document).on("click", ".cancel-edit", function() {
        location.reload();
    });

    // Edit comment form submission
    $(document).on("submit", "#editCommentForm", function(event) {
        event.preventDefault();
        submitForm({
            form: $(this),
            hasFiles: true,
            reloadOnSuccess: true
        });
    });

    // Delete comment button click
    $(document).on("click", ".delete-comment", function() {
        var commentId = $(this).data("id");
        confirmDelete({
            url: BASE_URL + "/api/comments/delete.php",
            data: { comment_id: commentId },
            confirmMessage: "Are you sure you want to delete this comment?",
            reloadOnSuccess: true
        });
    });

    // Delete file button click
    $(document).on("click", ".delete-file", function() {
        var fileId = $(this).data("id");
        var fileCard = $(this).closest(".file-card");

        if (confirm("Are you sure you want to delete this file?")) {
            ajaxRequest({
                url: BASE_URL + "/api/files/delete.php",
                data: { file_id: fileId },
                onSuccess: function() {
                    fileCard.fadeOut(function() {
                        $(this).remove();
                    });
                },
                onError: function(message) {
                    alert(message);
                }
            });
        }
    });

    // Filter event handlers
    $("#filter-status, #filter-priority, #filter-assignee").change(filterTickets);
    $("#ticket-search").on("input", filterTickets);

    // Initialize filters if present on page
    if ($("#filter-status").length) {
        initializeFilters();
    }
});

/**
 * Initialize ticket filters from URL params
 */
function initializeFilters() {
    var urlParams = new URLSearchParams(window.location.search);

    if (urlParams.has('status')) $("#filter-status").val(urlParams.get('status'));
    if (urlParams.has('priority')) $("#filter-priority").val(urlParams.get('priority'));
    if (urlParams.has('assignee')) $("#filter-assignee").val(urlParams.get('assignee'));
    if (urlParams.has('search')) $("#ticket-search").val(urlParams.get('search'));

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
        var priorityMatch = priority === "" || ticketPriorityId === priority;
        var assigneeMatch = assignee === "" || ticketAssignee === assignee || (assignee === "unassigned" && !ticketAssignee);
        var searchMatch = search === "" || ticketTitle.indexOf(search) > -1;

        $(this).toggle(statusMatch && priorityMatch && assigneeMatch && searchMatch);
    });

    // Update URL without page refresh
    var url = new URL(window.location.href);
    status ? url.searchParams.set('status', status) : url.searchParams.delete('status');
    priority ? url.searchParams.set('priority', priority) : url.searchParams.delete('priority');
    assignee ? url.searchParams.set('assignee', assignee) : url.searchParams.delete('assignee');
    search ? url.searchParams.set('search', search) : url.searchParams.delete('search');
    window.history.replaceState({}, '', url);
}
