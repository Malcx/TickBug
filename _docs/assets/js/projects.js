/**
 * assets/js/projects.js
 * Project related JavaScript functionality
 * Uses utils.js for common functions
 */

$(document).ready(function() {
    // Create project form submission
    $("#createProjectForm").submit(function(event) {
        event.preventDefault();
        submitForm({
            form: $(this),
            successRedirect: BASE_URL + "/projects.php"
        });
    });

    // Edit project form submission
    $("#editProjectForm").submit(function(event) {
        event.preventDefault();
        var projectId = $("#editProjectForm input[name='project_id']").val();
        submitForm({
            form: $(this),
            showSuccessMessage: true,
            successRedirect: BASE_URL + "/projects.php?id=" + projectId,
            redirectDelay: 1500
        });
    });

    // Add user to project form submission
    $("#addUserForm").submit(function(event) {
        event.preventDefault();
        ajaxRequest({
            url: BASE_URL + "/api/projects/users.php",
            data: $(this).serialize() + "&action=add",
            reloadOnSuccess: true,
            onError: function(message) {
                showFormError($("#addUserForm"), message);
            }
        });
    });

    // Change user role
    $(".change-role").change(function() {
        var userId = $(this).data("user");
        var role = $(this).val();
        var projectId = $("#project_id").val();

        ajaxRequest({
            url: BASE_URL + "/api/projects/users.php",
            data: {
                action: "change_role",
                project_id: projectId,
                user_id: userId,
                role: role
            },
            showNotifications: true,
            successMessage: "User role updated successfully.",
            onError: function() {
                location.reload();
            }
        });
    });

    // Remove user from project
    $(".remove-user").click(function() {
        var userId = $(this).data("user");
        var projectId = $("#project_id").val();

        confirmDelete({
            url: BASE_URL + "/api/projects/users.php",
            data: {
                action: "remove",
                project_id: projectId,
                user_id: userId
            },
            confirmMessage: "Are you sure you want to remove this user from the project?",
            reloadOnSuccess: true
        });
    });

    // Archive project
    $(".archive-project").click(function() {
        var projectId = $(this).data("id");

        confirmDelete({
            url: BASE_URL + "/api/projects/archive.php",
            data: { project_id: projectId },
            confirmMessage: "Are you sure you want to archive this project?",
            successRedirect: BASE_URL + "/projects.php"
        });
    });

    // Unarchive project
    $(".unarchive-project").click(function() {
        var projectId = $(this).data("id");

        confirmDelete({
            url: BASE_URL + "/api/projects/unarchive.php",
            data: { project_id: projectId },
            confirmMessage: "Are you sure you want to unarchive this project?",
            successRedirect: BASE_URL + "/projects.php"
        });
    });

    // Initialize sortable for projects
    if ($("#projects-container").length) {
        $("#projects-container").sortable({
            items: ".col-4",
            handle: ".card-header",
            cursor: "move",
            opacity: 0.8,
            helper: "clone",
            forcePlaceholderSize: true,
            placeholder: "col-4 project-placeholder",
            start: function(event, ui) {
                ui.placeholder.height(ui.item.height());
                ui.placeholder.width(ui.item.width());
                ui.helper.css('z-index', 1000);
                ui.helper.find('.card').addClass('dragging');
            },
            stop: function(event, ui) {
                ui.item.find('.card').removeClass('dragging');
            },
            update: function(event, ui) {
                var projects = $(this).children(".col-4").toArray();
                var projectOrder = projects.map(function(project) {
                    return $(project).find(".card").data("id");
                });

                ajaxRequest({
                    url: BASE_URL + "/api/projects/reorder.php",
                    data: { order: projectOrder },
                    showNotifications: true,
                    successMessage: "Projects reordered successfully."
                });
            }
        });
    }

    // Toggle filter on projects list
    $("#filter-toggle").click(function() {
        $("#filter-form").slideToggle();
    });
});
