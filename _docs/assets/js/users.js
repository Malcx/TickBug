/**
 * assets/js/users.js
 * User profile and notification preferences JavaScript
 */

$(document).ready(function() {
    // Update profile form submission
    $("#updateProfileForm").submit(function(event) {
        event.preventDefault();
        
        // Validate form
        var firstName = $("#firstName").val().trim();
        var lastName = $("#lastName").val().trim();
        var email = $("#email").val().trim();
        var newPassword = $("#newPassword").val();
        var confirmPassword = $("#confirmPassword").val();
        
        // Basic validation
        if (!firstName || !lastName || !email) {
            showFormError($(this), "Name and email are required.");
            return;
        }
        
        // If changing password, validate passwords match
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
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    showFormSuccess($("#updateProfileForm"), response.message);
                    
                    // Clear password fields
                    $("#currentPassword").val("");
                    $("#newPassword").val("");
                    $("#confirmPassword").val("");
                } else {
                    showFormError($("#updateProfileForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#updateProfileForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Notification preferences form submission
    $("#notificationPreferencesForm").submit(function(event) {
        event.preventDefault();
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            data: $(this).serialize(),
            dataType: "json",
            success: function(response) {
                if (response.success) {
                    showFormSuccess($("#notificationPreferencesForm"), response.message);
                } else {
                    showFormError($("#notificationPreferencesForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#notificationPreferencesForm"), "An error occurred. Please try again.");
            }
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