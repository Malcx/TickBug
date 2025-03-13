/**
 * assets/js/auth.js
 * Authentication related JavaScript
 */

$(document).ready(function() {
    // Login form submission
    $("#loginForm").submit(function(event) {
        event.preventDefault();
        
        const email = $("#email").val().trim();
        const password = $("#password").val();
        
        // Validate form
        if (!email || !password) {
            showFormError($(this), "Email and password are required.");
            return;
        }
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            dataType: "json",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    window.location.href = BASE_URL + "/projects.php";
                } else {
                    showFormError($("#loginForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#loginForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Registration form submission
    $("#registerForm").submit(function(event) {
        event.preventDefault();
        
        const firstName = $("#firstName").val().trim();
        const lastName = $("#lastName").val().trim();
        const email = $("#email").val().trim();
        const password = $("#password").val();
        const confirmPassword = $("#confirmPassword").val();
        
        // Validate form
        if (!firstName || !lastName || !email || !password || !confirmPassword) {
            showFormError($(this), "All fields are required.");
            return;
        }
        
        // Validate email format
        if (!isValidEmail(email)) {
            showFormError($(this), "Please enter a valid email address.");
            return;
        }
        
        // Validate password length
        if (password.length < 8) {
            showFormError($(this), "Password must be at least 8 characters long.");
            return;
        }
        
        // Check if passwords match
        if (password !== confirmPassword) {
            showFormError($(this), "Passwords do not match.");
            return;
        }
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            dataType: "json",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    window.location.href = BASE_URL + "/login.php";
                } else {
                    showFormError($("#registerForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#registerForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Forgot password form submission
    $("#forgotPasswordForm").submit(function(event) {
        event.preventDefault();
        
        const email = $("#email").val().trim();
        
        // Validate form
        if (!email) {
            showFormError($(this), "Email is required.");
            return;
        }
        
        // Validate email format
        if (!isValidEmail(email)) {
            showFormError($(this), "Please enter a valid email address.");
            return;
        }
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            dataType: "json",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    showFormSuccess($("#forgotPasswordForm"), response.message);
                    $("#forgotPasswordForm")[0].reset();
                } else {
                    showFormError($("#forgotPasswordForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#forgotPasswordForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Reset password form submission
    $("#resetPasswordForm").submit(function(event) {
        event.preventDefault();
        
        const password = $("#password").val();
        const confirmPassword = $("#confirmPassword").val();
        
        // Validate form
        if (!password || !confirmPassword) {
            showFormError($(this), "All fields are required.");
            return;
        }
        
        // Validate password length
        if (password.length < 8) {
            showFormError($(this), "Password must be at least 8 characters long.");
            return;
        }
        
        // Check if passwords match
        if (password !== confirmPassword) {
            showFormError($(this), "Passwords do not match.");
            return;
        }
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            dataType: "json",
            data: $(this).serialize(),
            success: function(response) {
                if (response.success) {
                    showFormSuccess($("#resetPasswordForm"), response.message);
                    setTimeout(function() {
                        window.location.href = BASE_URL + "/login.php";
                    }, 3000);
                } else {
                    showFormError($("#resetPasswordForm"), response.message);
                }
            },
            error: function() {
                showFormError($("#resetPasswordForm"), "An error occurred. Please try again.");
            }
        });
    });
    
    // Update profile form submission
    $("#updateProfileForm").submit(function(event) {
        event.preventDefault();
        
        const firstName = $("#firstName").val().trim();
        const lastName = $("#lastName").val().trim();
        const email = $("#email").val().trim();
        const newPassword = $("#newPassword").val();
        const confirmPassword = $("#confirmPassword").val();
        
        // Validate required fields
        if (!firstName || !lastName || !email) {
            showFormError($(this), "Name and email are required.");
            return;
        }
        
        // Validate email format
        if (!isValidEmail(email)) {
            showFormError($(this), "Please enter a valid email address.");
            return;
        }
        
        // If changing password, validate
        if (newPassword) {
            const currentPassword = $("#currentPassword").val();
            
            // Validate current password
            if (!currentPassword) {
                showFormError($(this), "Current password is required to set a new password.");
                return;
            }
            
            // Validate password length
            if (newPassword.length < 8) {
                showFormError($(this), "New password must be at least 8 characters long.");
                return;
            }
            
            // Check if passwords match
            if (newPassword !== confirmPassword) {
                showFormError($(this), "New passwords do not match.");
                return;
            }
        }
        
        // Submit form via AJAX
        $.ajax({
            url: $(this).attr("action"),
            type: "POST",
            dataType: "json",
            data: $(this).serialize(),
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
    
    // Toggle password visibility
    $(".toggle-password").click(function() {
        const input = $($(this).attr("toggle"));
        if (input.attr("type") === "password") {
            input.attr("type", "text");
            $(this).text("Hide");
        } else {
            input.attr("type", "password");
            $(this).text("Show");
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

/**
 * Validate email format
 * 
 * @param {string} email Email to validate
 * @return {boolean} True if valid, false otherwise
 */
function isValidEmail(email) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}