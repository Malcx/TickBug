/**
 * assets/js/deliverables.js
 * Deliverable related JavaScript functionality
 * Uses utils.js for common functions
 */

$(document).ready(function() {
    // Create deliverable form submission
    $("#createDeliverableForm").submit(function(event) {
        event.preventDefault();
        submitForm({
            form: $(this),
            successRedirect: BASE_URL + "/projects.php?id=" + $("#project_id").val()
        });
    });

    // Edit deliverable form submission
    $("#editDeliverableForm").submit(function(event) {
        event.preventDefault();
        var projectId = $("#project_id").val();
        submitForm({
            form: $(this),
            showSuccessMessage: true,
            successRedirect: BASE_URL + "/projects.php?id=" + projectId,
            redirectDelay: 1500
        });
    });

    // Delete deliverable button click
    $(".delete-deliverable").click(function() {
        var deliverableId = $(this).data("id");
        confirmDelete({
            url: BASE_URL + "/api/deliverables/delete.php",
            data: { deliverable_id: deliverableId },
            confirmMessage: "Are you sure you want to delete this deliverable? This will also delete all tickets in this deliverable.",
            successRedirect: BASE_URL + "/projects.php?id=" + $("#project_id").val()
        });
    });
});
