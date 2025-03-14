/**
 * assets/js/deliverable-view.js
 * JavaScript for compact deliverable view with sortable tickets and persistent filters
 */

$(document).ready(function() {
    // Initialize ticket filters
    initializeFilters();
    
    // Initialize sortable for tickets
    initSortable();
    
    // Filter tickets by status
    $("#filter-status").change(function() {
        saveFilterState();
        filterTickets();
    });
    
    // Filter tickets by priority
    $("#filter-priority").change(function() {
        saveFilterState();
        filterTickets();
    });
    
    // Filter tickets by assignee
    $("#filter-assignee").change(function() {
        saveFilterState();
        filterTickets();
    });
    
    // Search tickets
    $("#ticket-search").on("input", function() {
        saveFilterState();
        filterTickets();
    });
});

/**
 * Initialize sortable functionality for tickets
 */
function initSortable() {
    $("#sortable-tickets").sortable({
        items: ".ticket-card",
        placeholder: "ui-sortable-placeholder",
        axis: "y",
        cursor: "grabbing",
        opacity: 0.8,
        update: function(event, ui) {
            saveTicketOrder();
        },
        // Prevent click event when sorting ends
        stop: function(event, ui) {
            // Cancel click events that might be triggered right after sorting
            ui.item.one('click', function(e) {
                e.stopPropagation();
                return false;
            });
        }
    }).disableSelection(); // Prevents text selection during drag
    
    // Make the title links still clickable
    $(".ticket-title").on("click", function(e) {
        e.stopPropagation();
        // Allow the default action (following the link)
        return true;
    });
}

/**
 * Save the new ticket order to the server
 */
function saveTicketOrder() {
    // Get the ticket IDs in the new order
    var ticketIds = [];
    $("#sortable-tickets .ticket-card").each(function() {
        ticketIds.push($(this).data("id"));
    });
    
    // Show a small notification that order is being saved
    showSavingNotification();
    
    // Send the new order to the server
    $.ajax({
        url: baseUrl + "/api/tickets/reorder.php",
        type: "POST",
        data: {
            deliverable_id: deliverableId,
            order: ticketIds
        },
        dataType: "json",
        success: function(response) {
            if (response.success) {
                showSuccessNotification("Ticket order saved");
            } else {
                showErrorNotification("Failed to save order: " + response.message);
                // Consider reverting the order in the UI if the server update fails
            }
        },
        error: function() {
            showErrorNotification("Server error while saving order");
        }
    });
}

/**
 * Show a temporary notification in the top-right corner
 */
function showSavingNotification() {
    showNotification("Saving order...", "notification-saving");
}

function showSuccessNotification(message) {
    showNotification(message, "notification-success");
}

function showErrorNotification(message) {
    showNotification(message, "notification-error");
}

function showNotification(message, className) {
    // Remove any existing notifications
    $(".floating-notification").remove();
    
    // Create a new notification
    var notification = $('<div class="floating-notification ' + className + '">' + message + '</div>');
    $("body").append(notification);
    
    // Show the notification
    setTimeout(function() {
        notification.addClass("show");
    }, 10);
    
    // Hide and remove the notification after a delay
    setTimeout(function() {
        notification.removeClass("show");
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 2000);
}

/**
 * Initialize ticket filters and restore previous selections
 */
function initializeFilters() {
    // Restore filter values from localStorage
    var savedFilters = getFilterState();
    
    if (savedFilters.status) {
        $("#filter-status").val(savedFilters.status);
    }
    
    if (savedFilters.priority) {
        $("#filter-priority").val(savedFilters.priority);
    }
    
    if (savedFilters.assignee) {
        $("#filter-assignee").val(savedFilters.assignee);
    }
    
    if (savedFilters.search) {
        $("#ticket-search").val(savedFilters.search);
    }
    
    // Apply filters immediately
    filterTickets();
}

/**
 * Save current filter state to localStorage
 */
function saveFilterState() {
    var currentFilters = {
        status: $("#filter-status").val(),
        priority: $("#filter-priority").val(),
        assignee: $("#filter-assignee").val(),
        search: $("#ticket-search").val(),
        deliverableId: deliverableId
    };
    
    localStorage.setItem('ticketFilters', JSON.stringify(currentFilters));
}

/**
 * Get saved filter state from localStorage
 */
function getFilterState() {
    var defaultFilters = {
        status: '',
        priority: '',
        assignee: '',
        search: '',
        deliverableId: deliverableId
    };
    
    var savedFilters = localStorage.getItem('ticketFilters');
    if (!savedFilters) {
        return defaultFilters;
    }
    
    try {
        var filters = JSON.parse(savedFilters);
        // Only use saved filters if they're for the current deliverable
        if (filters.deliverableId === deliverableId) {
            return filters;
        }
        return defaultFilters;
    } catch (e) {
        return defaultFilters;
    }
}

/**
 * Filter tickets based on selected criteria
 */
function filterTickets() {
    var status = $("#filter-status").val();
    var priority = $("#filter-priority").val();
    var assignee = $("#filter-assignee").val();
    var search = $("#ticket-search").val().toLowerCase();
    
    $(".ticket-card").each(function() {
        var ticketStatus = $(this).data("status");
        var ticketPriority = $(this).data("priority");
        var ticketAssignee = $(this).data("assignee");
        var ticketTitle = $(this).find("h5").text().toLowerCase();
        
        // Special handling for "open" and "closed" status filters
        var statusMatch = true;
        if (status === "open") {
            statusMatch = (ticketStatus !== "Complete" && ticketStatus !== "Rejected" && ticketStatus !== "Ignored");
        } else if (status === "closed") {
            statusMatch = (ticketStatus === "Complete" || ticketStatus === "Rejected" || ticketStatus === "Ignored");
        } else {
            statusMatch = (status === "" || ticketStatus === status);
        }
        
        var priorityMatch = (priority === "" || ticketPriority === priority);
        
        // Special handling for "Assigned to me" filter
        var assigneeMatch = true;
        if (assignee === "me") {
            // Current user ID is available as a global variable (currentUserId)
            assigneeMatch = (ticketAssignee == currentUserId);
        } else if (assignee === "unassigned") {
            assigneeMatch = (!ticketAssignee);
        } else {
            assigneeMatch = (assignee === "" || ticketAssignee == assignee);
        }
        
        var searchMatch = (search === "" || ticketTitle.indexOf(search) > -1);
        
        if (statusMatch && priorityMatch && assigneeMatch && searchMatch) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
}