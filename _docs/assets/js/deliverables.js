/**
 * assets/js/deliverables.js
 * Deliverable related JavaScript functionality
 */

$(document).ready(function() {
    // Create deliverable form submission
    $("#createDeliverableForm").submit(function(event) {
        event.preventDefault();
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    // Redirect to project page
                    window.location.href = BASE_URL + "/projects.php?id=" + $("#project_id").val();
                } else {
                    showFormError($("#createDeliverableForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#createDeliverableForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Edit deliverable form submission
    $("#editDeliverableForm").submit(function(event) {
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
                    showFormSuccess($("#editDeliverableForm"), response.message);
                    
                    // Redirect back to project view after short delay
                    setTimeout(function() {
                        var deliverable = $("#editDeliverableForm").data("deliverable");
                        var projectId = $("#project_id").val();
                        window.location.href = BASE_URL + "/projects.php?id=" + projectId;
                    }, 1500);
                } else {
                    showFormError($("#editDeliverableForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#editDeliverableForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Delete deliverable button click
    $(".delete-deliverable").click(function() {
        if (confirm("Are you sure you want to delete this deliverable? This will also delete all tickets in this deliverable.")) {
            var deliverableId = $(this).data("id");
            
            // Submit delete request via AJAX
            $.ajax({
                url: BASE_URL + "/api/deliverables/delete.php",
                type: "POST",
                data: {
                    deliverable_id: deliverableId
                },
                dataType: "json",
                success: function(response) {
                    if (response.success) {
                        // Redirect to project page
                        window.location.href = BASE_URL + "/projects.php?id=" + $("#project_id").val();
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