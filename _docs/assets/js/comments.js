/**
 * assets/js/comments.js
 * Comment functionality JavaScript
 * Uses utils.js for common functions
 */

$(document).ready(function() {
    // Initialize comment editor if available
    initCommentEditor();

    // Add comment form submission
    $("#addCommentForm").submit(function(event) {
        event.preventDefault();
        submitForm({
            form: $(this),
            hasFiles: true,
            onSuccess: function(response) {
                addCommentToDOM(response.comment);
                $("#addCommentForm")[0].reset();
                if (typeof resetEditor === 'function') {
                    resetEditor();
                }
            }
        });
    });

    // Edit comment functionality
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
            '<label for="edit-description">Comment</label>' +
            '<textarea id="edit-description" name="description" rows="3" class="form-control" required>' + commentDescription + '</textarea>' +
            '</div>' +
            '<div class="form-group">' +
            '<label for="edit-url">URL (optional)</label>' +
            '<input type="url" id="edit-url" name="url" class="form-control" value="' + commentUrl + '">' +
            '</div>' +
            '<div class="form-group">' +
            '<label for="edit-files">Add More Files (optional)</label>' +
            '<input type="file" id="edit-files" name="files[]" class="form-control" multiple>' +
            '</div>' +
            '<button type="submit" class="btn btn-primary">Update Comment</button> ' +
            '<button type="button" class="btn btn-secondary cancel-edit">Cancel</button>' +
            '</form>');

        commentCard.find(".card-body").html(editForm);
        initCommentEditor("#edit-description");
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

    // Delete comment
    $(document).on("click", ".delete-comment", function() {
        var commentId = $(this).data("id");
        var commentCard = $(this).closest(".comment-card");

        confirmDelete({
            url: BASE_URL + "/api/comments/delete.php",
            data: { comment_id: commentId },
            confirmMessage: "Are you sure you want to delete this comment?",
            onSuccess: function() {
                commentCard.fadeOut(function() {
                    $(this).remove();
                });
            }
        });
    });
});

/**
 * Initialize comment editor (placeholder for rich text editor)
 */
function initCommentEditor(selector) {
    // Placeholder for rich text editor initialization
}

/**
 * Reset comment editor (placeholder for rich text editor)
 */
function resetEditor() {
    // Placeholder for rich text editor reset
}

/**
 * Add a new comment to the DOM
 */
function addCommentToDOM(comment) {
    var commentHtml = '<div class="card mb-3 comment-card" data-id="' + comment.comment_id + '">' +
        '<div class="card-header">' +
        '<div class="row">' +
        '<div class="col-8">' +
        '<strong>' + comment.first_name + ' ' + comment.last_name + '</strong> ' +
        '<span class="text-muted ml-2">Just now</span>' +
        '</div>' +
        '<div class="col-4 text-right">' +
        '<button class="btn btn-sm btn-secondary edit-comment" data-id="' + comment.comment_id + '">Edit</button> ' +
        '<button class="btn btn-sm btn-danger delete-comment" data-id="' + comment.comment_id + '">Delete</button>' +
        '</div>' +
        '</div>' +
        '</div>' +
        '<div class="card-body">' +
        '<div class="comment-content">' +
        comment.description.replace(/\n/g, '<br>') +
        (comment.url ? '<p class="mt-3"><strong>URL:</strong> <a href="' + comment.url + '" target="_blank">' + comment.url + '</a></p>' : '') +
        '</div>' +
        '</div>' +
        '</div>';

    $("#comments-container").append(commentHtml);

    $('html, body').animate({
        scrollTop: $("#comments-container .comment-card:last-child").offset().top - 20
    }, 300);
}
