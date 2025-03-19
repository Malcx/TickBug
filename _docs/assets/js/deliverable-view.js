/**
 * assets/js/deliverable-view.js
 * Enhanced JavaScript for the deliverable view with priority grouping and drag-drop
 */

$(document).ready(function() {
    // Initialize sortable for each priority group
    initSortable();
    
    // Initialize ticket filters
    initializeFilters();
    
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
 * Initialize sortable functionality for tickets within priority groups
 */
function initSortable() {
    $(".sortable-tickets").sortable({
        items: ".ticket-card",
        placeholder: "ui-sortable-placeholder",
        connectWith: false, // Only allow sorting within the same priority group
        cursor: "grabbing",
        opacity: 0.8,
        helper: "clone",
        forcePlaceholderSize: true,
        tolerance: "pointer",
        // Add touch settings
        delay: 150, // Delay for touch devices to prevent accidental drags
        distance: 10, // Minimum distance to start drag - helps on touch devices
        cancel: ".ticket-title, a, button", // Prevent drag from starting on interactive elements
        scroll: true, // Enable scrolling during drag - important for mobile
        // Ensure handle is used for better mobile experience
        handle: ".drag-handle",
        start: function(event, ui) {
            ui.placeholder.height(ui.item.outerHeight());
            ui.item.addClass("ui-sortable-helper");
            $(this).addClass("ui-sortable-active");
        },
        over: function(event, ui) {
            $(this).addClass("ui-sortable-hover");
        },
        out: function(event, ui) {
            $(this).removeClass("ui-sortable-hover");
        },
        stop: function(event, ui) {
            ui.item.removeClass("ui-sortable-helper");
            $(".sortable-tickets").removeClass("ui-sortable-active ui-sortable-hover");
            // Cancel click events that might be triggered right after sorting
            ui.item.one('click', function(e) {
                e.stopPropagation();
                return false;
            });
        },
        update: function(event, ui) {
            // Get the priority and deliverable ID for this container
            const priorityGroup = $(this).data("priority");
            const deliverableId = $(this).data("deliverable-id");
            
            // Get all ticket IDs in this container in the new order
            const ticketIds = $(this).find(".ticket-card").map(function() {
                return $(this).data("id");
            }).get();
            
            // Save the new order to the database
            saveTicketOrder(deliverableId, priorityGroup, ticketIds);
        }
    }).disableSelection(); // Prevents text selection during drag
}

/**
 * Save ticket order to the server
 * 
 * @param {number} deliverableId - The ID of the deliverable
 * @param {string} priorityGroup - The priority group name
 * @param {Array} ticketIds - Array of ticket IDs in the new order
 */
function saveTicketOrder(deliverableId, priorityGroup, ticketIds) {
    // Show saving notification
    showSavingNotification();
    
    // Send AJAX request to save the new order
    $.ajax({
        url: baseUrl + "/api/tickets/reorder.php",
        type: "POST",
        data: {
            deliverable_id: deliverableId,
            priority: priorityGroup,
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
    
    var visibleCount = 0;
    var visibleByPriority = {};
    
    // Initialize priority visibility counter
    $(".priority-group").each(function() {
        visibleByPriority[$(this).data("priority")] = 0;
    });
    
    // First, filter all ticket cards
    $(".ticket-card").each(function() {
        var ticketStatus = $(this).data("status");
        var ticketPriority = $(this).data("priority");
        var ticketAssignee = $(this).data("assignee");
        var ticketTitle = $(this).find("h5 a").text().toLowerCase();
        
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
        
        // Apply filter to this ticket
        if (statusMatch && priorityMatch && assigneeMatch && searchMatch) {
            $(this).show();
            visibleCount++;
            
            // Increment counter for this priority group
            var ticketPriorityGroup = $(this).closest(".priority-group").data("priority");
            visibleByPriority[ticketPriorityGroup]++;
        } else {
            $(this).hide();
        }
    });
    
    // Now show/hide priority groups based on whether they have visible tickets
    $(".priority-group").each(function() {
        var priorityKey = $(this).data("priority");
        if (visibleByPriority[priorityKey] > 0) {
            $(this).show();
            
            // Update the ticket count in the header
            $(this).find(".ticket-count").text(visibleByPriority[priorityKey] + " tickets");
        } else {
            $(this).hide();
        }
    });
    
    // Check if any tickets are visible
    if (visibleCount === 0) {
        // Show no tickets message
        $(".no-tickets-message").show();
    } else {
        // Hide the message
        $(".no-tickets-message").hide();
    }
}