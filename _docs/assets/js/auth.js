/**
 * assets/js/auth.js
 * Authentication related JavaScript
 * Uses utils.js for common functions (showFormError, showFormSuccess, submitForm, isValidEmail)
 */

$(document).ready(function() {
    // Login form submission
    $("#loginForm").submit(function(event) {
        event.preventDefault();

        var email = $("#email").val().trim();
        var password = $("#password").val();

        if (!email || !password) {
            showFormError($(this), "Email and password are required.");
            return;
        }

        submitForm({
            form: $(this),
            successRedirect: BASE_URL + "/projects.php"
        });
    });

    // Registration form submission
    $("#registerForm").submit(function(event) {
        event.preventDefault();

        var firstName = $("#firstName").val().trim();
        var lastName = $("#lastName").val().trim();
        var email = $("#email").val().trim();
        var password = $("#password").val();
        var confirmPassword = $("#confirmPassword").val();

        if (!firstName || !lastName || !email || !password || !confirmPassword) {
            showFormError($(this), "All fields are required.");
            return;
        }

        if (!isValidEmail(email)) {
            showFormError($(this), "Please enter a valid email address.");
            return;
        }

        if (password.length < 8) {
            showFormError($(this), "Password must be at least 8 characters long.");
            return;
        }

        if (password !== confirmPassword) {
            showFormError($(this), "Passwords do not match.");
            return;
        }

        submitForm({
            form: $(this),
            successRedirect: BASE_URL + "/login.php"
        });
    });

    // Forgot password form submission
    $("#forgotPasswordForm").submit(function(event) {
        event.preventDefault();

        var email = $("#email").val().trim();

        if (!email) {
            showFormError($(this), "Email is required.");
            return;
        }

        if (!isValidEmail(email)) {
            showFormError($(this), "Please enter a valid email address.");
            return;
        }

        submitForm({
            form: $(this),
            showSuccessMessage: true,
            onSuccess: function() {
                $("#forgotPasswordForm")[0].reset();
            }
        });
    });

    // Reset password form submission
    $("#resetPasswordForm").submit(function(event) {
        event.preventDefault();

        var password = $("#password").val();
        var confirmPassword = $("#confirmPassword").val();

        if (!password || !confirmPassword) {
            showFormError($(this), "All fields are required.");
            return;
        }

        if (password.length < 8) {
            showFormError($(this), "Password must be at least 8 characters long.");
            return;
        }

        if (password !== confirmPassword) {
            showFormError($(this), "Passwords do not match.");
            return;
        }

        submitForm({
            form: $(this),
            showSuccessMessage: true,
            successRedirect: BASE_URL + "/login.php",
            redirectDelay: 3000
        });
    });

    // Toggle password visibility
    $(".toggle-password").click(function() {
        var input = $($(this).attr("toggle") || $(this).data("toggle"));
        if (input.attr("type") === "password") {
            input.attr("type", "text");
            $(this).text("Hide");
        } else {
            input.attr("type", "password");
            $(this).text("Show");
        }
    });
});
