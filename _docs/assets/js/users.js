/**
 * assets/js/users.js
 * User profile and notification preferences JavaScript
 * Uses utils.js for common functions
 */

$(document).ready(function() {
    // Update profile form submission
    $("#updateProfileForm").submit(function(event) {
        event.preventDefault();

        var firstName = $("#firstName").val().trim();
        var lastName = $("#lastName").val().trim();
        var email = $("#email").val().trim();
        var newPassword = $("#newPassword").val();
        var confirmPassword = $("#confirmPassword").val();

        if (!firstName || !lastName || !email) {
            showFormError($(this), "Name and email are required.");
            return;
        }

        if (newPassword) {
            var currentPassword = $("#currentPassword").val();

            if (!currentPassword) {
                showFormError($(this), "Current password is required to set a new password.");
                return;
            }

            if (newPassword.length < 8) {
                showFormError($(this), "New password must be at least 8 characters long.");
                return;
            }

            if (newPassword !== confirmPassword) {
                showFormError($(this), "New passwords do not match.");
                return;
            }
        }

        submitForm({
            form: $(this),
            showSuccessMessage: true,
            onSuccess: function() {
                $("#currentPassword, #newPassword, #confirmPassword").val("");
            }
        });
    });

    // Notification preferences form submission
    $("#notificationPreferencesForm").submit(function(event) {
        event.preventDefault();
        submitForm({
            form: $(this),
            showSuccessMessage: true
        });
    });

    // Show/hide password toggle
    $(".toggle-password").click(function() {
        var input = $($(this).data("toggle"));
        if (input.attr("type") === "password") {
            input.attr("type", "text");
            $(this).text("Hide");
        } else {
            input.attr("type", "password");
            $(this).text("Show");
        }
    });

    // User search functionality for adding to projects
    $("#userSearch").keyup(function() {
        var searchTerm = $(this).val().trim();

        if (searchTerm.length < 2) {
            $("#userSearchResults").empty();
            return;
        }

        $.ajax({
            url: BASE_URL + "/api/users/search.php",
            type: "GET",
            data: { term: searchTerm },
            dataType: "json",
            success: function(response) {
                var resultsHtml = "";

                if (response.success && response.users.length > 0) {
                    $.each(response.users, function(index, user) {
                        resultsHtml += '<div class="user-result" data-email="' + user.email + '">' +
                            '<strong>' + user.first_name + ' ' + user.last_name + '</strong> ' +
                            '<span class="text-muted">(' + user.email + ')</span>' +
                            '</div>';
                    });
                } else {
                    resultsHtml = '<div class="text-muted">No users found.</div>';
                }

                $("#userSearchResults").html(resultsHtml);
            }
        });
    });

    // Handle user selection from search results
    $(document).on("click", ".user-result", function() {
        var email = $(this).data("email");
        $("#email").val(email);
        $("#userSearchResults").empty();
    });
});
