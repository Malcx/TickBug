/**
 * assets/js/utils.js
 * Shared utility functions for the TickBug application
 * Consolidates common functionality used across multiple JS files
 */

/**
 * Show form error message
 *
 * @param {jQuery|string} form Form element or selector
 * @param {string} message Error message
 */
function showFormError(form, message) {
    var $form = $(form);

    // Remove existing error messages
    $form.find(".form-error").remove();

    // Add new error message
    $form.prepend('<div class="alert alert-danger form-error">' + message + '</div>');

    // Scroll to error message
    $('html, body').animate({
        scrollTop: $form.offset().top - 20
    }, 300);
}

/**
 * Show form success message
 *
 * @param {jQuery|string} form Form element or selector
 * @param {string} message Success message
 */
function showFormSuccess(form, message) {
    var $form = $(form);

    // Remove existing messages
    $form.find(".form-error, .form-success").remove();

    // Add new success message
    $form.prepend('<div class="alert alert-success form-success">' + message + '</div>');

    // Scroll to success message
    $('html, body').animate({
        scrollTop: $form.offset().top - 20
    }, 300);
}

/**
 * Show notification toast
 *
 * @param {string} type Notification type (success, error, info, warning)
 * @param {string} message Notification message
 * @param {number} duration Duration in ms (default: 3000)
 */
function showNotification(type, message, duration) {
    duration = duration || 3000;

    // Check if notification container exists
    var container = $("#notification-container");
    if (container.length === 0) {
        // Create container if it doesn't exist
        $("body").append('<div id="notification-container"></div>');
        container = $("#notification-container");
    }

    // Create notification element
    var notification = $('<div class="notification notification-' + type + '">' + message + '</div>');
    container.append(notification);

    // Show notification with animation
    setTimeout(function() {
        notification.addClass("show");
    }, 10);

    // Remove notification after delay
    setTimeout(function() {
        notification.removeClass("show");
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, duration);
}

/**
 * Validate email format
 *
 * @param {string} email Email to validate
 * @return {boolean} True if valid, false otherwise
 */
function isValidEmail(email) {
    var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return regex.test(email);
}

/**
 * Submit form via AJAX with standard handling
 *
 * @param {Object} options Configuration options
 * @param {jQuery|string} options.form Form element or selector
 * @param {string} options.url URL to submit to (defaults to form action)
 * @param {boolean} options.hasFiles Whether form has file uploads
 * @param {Function} options.onSuccess Success callback (receives response)
 * @param {Function} options.onError Error callback (receives message)
 * @param {string} options.successRedirect URL to redirect to on success
 * @param {number} options.redirectDelay Delay before redirect in ms (default: 0)
 * @param {boolean} options.showSuccessMessage Show success message from response
 */
function submitForm(options) {
    var $form = $(options.form);
    var url = options.url || $form.attr("action");
    var hasFiles = options.hasFiles || false;

    var ajaxOptions = {
        url: url,
        type: "POST",
        dataType: "json",
        success: function(response) {
            if (response.success) {
                if (options.showSuccessMessage && response.message) {
                    showFormSuccess($form, response.message);
                }

                if (options.onSuccess) {
                    options.onSuccess(response);
                }

                if (options.successRedirect) {
                    var delay = options.redirectDelay || 0;
                    if (delay > 0) {
                        setTimeout(function() {
                            window.location.href = options.successRedirect;
                        }, delay);
                    } else {
                        window.location.href = options.successRedirect;
                    }
                }
            } else {
                showFormError($form, response.message);
                if (options.onError) {
                    options.onError(response.message);
                }
            }
        },
        error: function(xhr, status, error) {
            var message = "An error occurred. Please try again.";

            if (xhr.responseText) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    message = response.message || message;
                } catch(e) {
                    console.error("AJAX Error:", status, error);
                }
            }

            showFormError($form, message);
            if (options.onError) {
                options.onError(message);
            }
        }
    };

    if (hasFiles) {
        ajaxOptions.data = new FormData($form[0]);
        ajaxOptions.contentType = false;
        ajaxOptions.processData = false;
    } else {
        ajaxOptions.data = $form.serialize();
    }

    $.ajax(ajaxOptions);
}

/**
 * Make an AJAX request with standard error handling
 *
 * @param {Object} options Configuration options
 * @param {string} options.url Request URL
 * @param {string} options.method HTTP method (default: POST)
 * @param {Object} options.data Request data
 * @param {Function} options.onSuccess Success callback
 * @param {Function} options.onError Error callback
 * @param {boolean} options.showNotifications Show success/error notifications
 * @param {string} options.successMessage Custom success message
 * @param {boolean} options.reloadOnSuccess Reload page on success
 */
function ajaxRequest(options) {
    $.ajax({
        url: options.url,
        type: options.method || "POST",
        data: options.data,
        dataType: "json",
        success: function(response) {
            if (response.success) {
                if (options.showNotifications) {
                    showNotification("success", options.successMessage || response.message || "Success!");
                }

                if (options.onSuccess) {
                    options.onSuccess(response);
                }

                if (options.reloadOnSuccess) {
                    location.reload();
                }
            } else {
                if (options.showNotifications) {
                    showNotification("error", response.message);
                }

                if (options.onError) {
                    options.onError(response.message);
                }
            }
        },
        error: function() {
            var message = "An error occurred. Please try again.";

            if (options.showNotifications) {
                showNotification("error", message);
            }

            if (options.onError) {
                options.onError(message);
            }
        }
    });
}

/**
 * Confirm and delete an item via AJAX
 *
 * @param {Object} options Configuration options
 * @param {string} options.url Delete API URL
 * @param {Object} options.data Request data (typically contains ID)
 * @param {string} options.confirmMessage Confirmation message
 * @param {Function} options.onSuccess Success callback
 * @param {string} options.successRedirect URL to redirect to on success
 * @param {boolean} options.reloadOnSuccess Reload page on success
 */
function confirmDelete(options) {
    var message = options.confirmMessage || "Are you sure you want to delete this item?";

    if (confirm(message)) {
        ajaxRequest({
            url: options.url,
            data: options.data,
            showNotifications: true,
            onSuccess: function(response) {
                if (options.onSuccess) {
                    options.onSuccess(response);
                }

                if (options.successRedirect) {
                    window.location.href = options.successRedirect;
                } else if (options.reloadOnSuccess) {
                    location.reload();
                }
            }
        });
    }
}

/**
 * Initialize common form submission handling
 * Automatically handles forms with data-ajax="true" attribute
 */
$(document).ready(function() {
    // Auto-handle forms marked for AJAX submission
    $('form[data-ajax="true"]').submit(function(event) {
        event.preventDefault();

        var $form = $(this);
        var hasFiles = $form.find('input[type="file"]').length > 0;
        var successRedirect = $form.data("success-redirect");
        var redirectDelay = $form.data("redirect-delay") || 0;
        var showSuccess = $form.data("show-success") !== false;

        submitForm({
            form: $form,
            hasFiles: hasFiles,
            successRedirect: successRedirect ? BASE_URL + successRedirect : null,
            redirectDelay: redirectDelay,
            showSuccessMessage: showSuccess
        });
    });
});
