/**
 * assets/js/comments.js
 * Comment functionality JavaScript
 */

$(document).ready(function() {
    // Initialize comment editor if available
    initCommentEditor();
    
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
                    // Add new comment to the comments container
                    addCommentToDOM(response.comment);
                    
                    // Clear form fields
                    $("#addCommentForm")[0].reset();
                    
                    // Reset editor if available
                    if (typeof resetEditor === 'function') {
                        resetEditor();
                    }
                } else {
                    showFormError($("#addCommentForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#addCommentForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Edit comment functionality
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
        
        // Replace comment content with edit form
        commentCard.find(".card-body").html(editForm);
        
        // Initialize comment editor for the edit form if available
        initCommentEditor("#edit-description");
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
    
    // Delete comment
    $(document).on("click", ".delete-comment", function() {
        if (confirm("Are you sure you want to delete this comment?")) {
            var commentId = $(this).data("id");
            var commentCard = $(this).closest(".comment-card");
            
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
                        // Remove comment from DOM
                        commentCard.fadeOut(function() {
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
});

/**
 * Initialize comment editor
 * 
 * @param {string} selector Textarea selector
 */
function initCommentEditor(selector) {
    // This is a placeholder function
    // If you want to implement a rich text editor (like TinyMCE, CKEditor, etc.),
    // you can add the initialization code here
    
    // Example (uncomment if using a rich text editor):
    /*
    if (typeof tinymce !== 'undefined') {
        tinymce.init({
            selector: selector || '#description',
            height: 200,
            menubar: false,
            plugins: [
                'advlist autolink lists link image charmap print preview anchor',
                'searchreplace visualblocks code fullscreen',
                'insertdatetime media table paste code help wordcount'
            ],
            toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help'
        });
    }
    */
}

/**
 * Reset comment editor
 */
function resetEditor() {
    // This is a placeholder function
    // If you implemented a rich text editor, add code to reset it here
    
    // Example (uncomment if using a rich text editor):
    /*
    if (typeof tinymce !== 'undefined') {
        tinymce.get('description').setContent('');
    }
    */
}

/**
 * Add a new comment to the DOM
 * 
 * @param {Object} comment Comment data
 */
function addCommentToDOM(comment) {
    // Create comment HTML
    var commentHtml = `
        <div class="card mb-3 comment-card" data-id="${comment.comment_id}">
            <div class="card-header">
                <div class="row">
                    <div class="col-8">
                        <strong>${comment.first_name} ${comment.last_name}</strong>
                        <span class="text-muted ml-2">Just now</span>
                    </div>
                    <div class="col-4 text-right">
                        <button class="btn btn-sm btn-secondary edit-comment" data-id="${comment.comment_id}">Edit</button>
                        <button class="btn btn-sm btn-danger delete-comment" data-id="${comment.comment_id}">Delete</button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="comment-content">
                    ${comment.description.replace(/\n/g, '<br>')}
                    ${comment.url ? `<p class="mt-3"><strong>URL:</strong> <a href="${comment.url}" target="_blank">${comment.url}</a></p>` : ''}
                </div>
            </div>
        </div>
    `;
    
    // Add to container
    $("#comments-container").append(commentHtml);
    
    // Scroll to new comment
    $('html, body').animate({
        scrollTop: $("#comments-container .comment-card:last-child").offset().top - 20
    }, 300);
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