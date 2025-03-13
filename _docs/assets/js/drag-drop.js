/**
 * assets/js/drag-drop.js
 * Drag and drop functionality for tickets
 */

$(document).ready(function() {
    // Initialize ticket drag and drop
    initTicketDragDrop();
    
    // Initialize deliverable reordering
    initDeliverableReordering();
});

/**
 * Initialize drag and drop for tickets
 */
function initTicketDragDrop() {
    // Make tickets draggable
    $(".ticket-card.draggable").draggable({
        revert: "invalid",
        helper: "clone",
        cursor: "move",
        opacity: 0.7,
        zIndex: 100,
        start: function(event, ui) {
            $(this).addClass("dragging");
        },
        stop: function(event, ui) {
            $(this).removeClass("dragging");
        }
    });
    
    // Make tickets containers droppable for reordering
    $(".tickets-container").droppable({
        accept: ".ticket-card.draggable",
        hoverClass: "drag-over",
        drop: function(event, ui) {
            const ticketId = ui.draggable.data("id");
            const sourceDeliverableId = ui.draggable.closest(".tickets-container").data("deliverable-id");
            const targetDeliverableId = $(this).data("deliverable-id");
            
            // If dropping in the same deliverable, handle reordering
            if (sourceDeliverableId === targetDeliverableId) {
                const tickets = $(this).children(".ticket-card").toArray();
                const ticketOrder = tickets.map(ticket => $(ticket).data("id"));
                
                // Update order in database
                updateTicketOrder(targetDeliverableId, ticketOrder);
            } else {
                // If dropping in a different deliverable, move the ticket
                moveTicket(ticketId, sourceDeliverableId, targetDeliverableId);
            }
        }
    });
    
    // Make user cards droppable for assignment
    $("#project-users .user-card").droppable({
        accept: ".ticket-card.draggable",
        hoverClass: "drag-over",
        drop: function(event, ui) {
            const ticketId = ui.draggable.data("id");
            const userId = $(this).data("id");
            
            // Assign ticket to user
            assignTicket(ticketId, userId);
        }
    });
    
    // Make status cards droppable for status change
    $("#status-container .status-card").droppable({
        accept: ".ticket-card.draggable",
        hoverClass: "drag-over",
        drop: function(event, ui) {
            const ticketId = ui.draggable.data("id");
            const status = $(this).data("status");
            
            // Change ticket status
            changeTicketStatus(ticketId, status);
        }
    });
}

/**
 * Initialize deliverable reordering
 */
function initDeliverableReordering() {
    $("#deliverables-container").sortable({
        handle: ".card-header",
        cursor: "move",
        opacity: 0.7,
        update: function(event, ui) {
            const deliverables = $(this).children(".deliverable-card").toArray();
            const deliverableOrder = deliverables.map(deliverable => $(deliverable).data("id"));
            
            // Update order in database
            updateDeliverableOrder(deliverableOrder);
        }
    });
}

/**
 * Update ticket order in the database
 * 
 * @param {number} deliverableId Deliverable ID
 * @param {Array} ticketOrder Array of ticket IDs in new order
 */
function updateTicketOrder(deliverableId, ticketOrder) {
    $.ajax({
        url: BASE_URL + "/api/tickets/reorder.php",
        type: "POST",
        dataType: "json",
        data: {
            deliverable_id: deliverableId,
            order: ticketOrder
        },
        success: function(response) {
            if (response.success) {
                showNotification("success", "Tickets reordered successfully.");
            } else {
                showNotification("error", response.message);
            }
        },
        error: function() {
            showNotification("error", "An error occurred while reordering tickets.");
        }
    });
}

/**
 * Move a ticket to a different deliverable
 * 
 * @param {number} ticketId Ticket ID
 * @param {number} sourceDeliverableId Source deliverable ID
 * @param {number} targetDeliverableId Target deliverable ID
 */
function moveTicket(ticketId, sourceDeliverableId, targetDeliverableId) {
    $.ajax({
        url: BASE_URL + "/api/tickets/update.php",
        type: "POST",
        dataType: "json",
        data: {
            ticket_id: ticketId,
            deliverable_id: targetDeliverableId,
            action: "move"
        },
        success: function(response) {
            if (response.success) {
                // Reload the page to reflect changes
                location.reload();
            } else {
                showNotification("error", response.message);
            }
        },
        error: function() {
            showNotification("error", "An error occurred while moving the ticket.");
        }
    });
}

/**
 * Assign a ticket to a user
 * 
 * @param {number} ticketId Ticket ID
 * @param {number} userId User ID
 */
function assignTicket(ticketId, userId) {
    $.ajax({
        url: BASE_URL + "/api/tickets/assign.php",
        type: "POST",
        dataType: "json",
        data: {
            ticket_id: ticketId,
            assigned_to: userId
        },
        success: function(response) {
            if (response.success) {
                // Update UI to show assignment
                const userCard = $("#project-users .user-card[data-id='" + userId + "']");
                const userName = userCard.find("strong").text();
                
                $(".ticket-card[data-id='" + ticketId + "'] small").text("Assigned to: " + userName);
                
                showNotification("success", "Ticket assigned successfully.");
            } else {
                showNotification("error", response.message);
            }
        },
        error: function() {
            showNotification("error", "An error occurred while assigning the ticket.");
        }
    });
}

/**
 * Change a ticket's status
 * 
 * @param {number} ticketId Ticket ID
 * @param {string} status New status
 */
function changeTicketStatus(ticketId, status) {
    $.ajax({
        url: BASE_URL + "/api/tickets/change-status.php",
        type: "POST",
        dataType: "json",
        data: {
            ticket_id: ticketId,
            status: status
        },
        success: function(response) {
            if (response.success) {
                // Update UI to show new status
                const ticketCard = $(".ticket-card[data-id='" + ticketId + "']");
                const statusBadge = ticketCard.find(".badge-new, .badge-needs-clarification, .badge-assigned, " +
                    ".badge-in-progress, .badge-in-review, .badge-complete, .badge-rejected, .badge-ignored");
                
                // Remove old status class
                const oldStatusClass = statusBadge.attr("class").split(" ").find(cls => cls.startsWith("badge-"));
                statusBadge.removeClass(oldStatusClass);
                
                // Add new status class
                const newStatusClass = "badge-" + status.toLowerCase().replace(/ /g, "-");
                statusBadge.addClass(newStatusClass);
                
                // Update status text
                statusBadge.text(status);
                
                showNotification("success", "Ticket status changed successfully.");
            } else {
                showNotification("error", response.message);
            }
        },
        error: function() {
            showNotification("error", "An error occurred while changing the ticket status.");
        }
    });
}

/**
 * Update deliverable order in the database
 * 
 * @param {Array} deliverableOrder Array of deliverable IDs in new order
 */
function updateDeliverableOrder(deliverableOrder) {
    $.ajax({
        url: BASE_URL + "/api/deliverables/reorder.php",
        type: "POST",
        dataType: "json",
        data: {
            order: deliverableOrder
        },
        success: function(response) {
            if (response.success) {
                showNotification("success", "Deliverables reordered successfully.");
            } else {
                showNotification("error", response.message);
            }
        },
        error: function() {
            showNotification("error", "An error occurred while reordering deliverables.");
        }
    });
}

/**
 * Show notification
 * 
 * @param {string} type Notification type (success, error)
 * @param {string} message Notification message
 */
function showNotification(type, message) {
    // Check if notification container exists
    let container = $("#notification-container");
    if (container.length === 0) {
        // Create container if it doesn't exist
        $("body").append('<div id="notification-container"></div>');
        container = $("#notification-container");
    }
    
    // Create notification element
    const notification = $('<div class="notification notification-' + type + '">' + message + '</div>');
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
    }, 3000);
}