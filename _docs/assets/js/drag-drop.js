/**
 * assets/js/drag-drop.js
 * Drag and drop functionality for tickets
 * Uses utils.js for common functions (ajaxRequest, showNotification)
 */

$(document).ready(function() {
    initTicketDragDrop();
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
        start: function() {
            $(this).addClass("dragging");
        },
        stop: function() {
            $(this).removeClass("dragging");
        }
    });

    // Make tickets containers droppable for reordering
    $(".tickets-container").droppable({
        accept: ".ticket-card.draggable",
        hoverClass: "drag-over",
        drop: function(event, ui) {
            var ticketId = ui.draggable.data("id");
            var sourceDeliverableId = ui.draggable.closest(".tickets-container").data("deliverable-id");
            var targetDeliverableId = $(this).data("deliverable-id");

            if (sourceDeliverableId === targetDeliverableId) {
                var tickets = $(this).children(".ticket-card").toArray();
                var ticketOrder = tickets.map(function(ticket) {
                    return $(ticket).data("id");
                });
                updateTicketOrder(targetDeliverableId, ticketOrder);
            } else {
                moveTicket(ticketId, sourceDeliverableId, targetDeliverableId);
            }
        }
    });

    // Make user cards droppable for assignment
    $("#project-users .user-card").droppable({
        accept: ".ticket-card.draggable",
        hoverClass: "drag-over",
        drop: function(event, ui) {
            var ticketId = ui.draggable.data("id");
            var userId = $(this).data("id");
            assignTicket(ticketId, userId);
        }
    });

    // Make status cards droppable for status change
    $("#status-container .status-card").droppable({
        accept: ".ticket-card.draggable",
        hoverClass: "drag-over",
        drop: function(event, ui) {
            var ticketId = ui.draggable.data("id");
            var statusId = $(this).data("status-id");
            var statusName = $(this).data("status");
            changeTicketStatus(ticketId, statusId, statusName);
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
        update: function() {
            var deliverables = $(this).children(".deliverable-card").toArray();
            var deliverableOrder = deliverables.map(function(deliverable) {
                return $(deliverable).data("id");
            });
            updateDeliverableOrder(deliverableOrder);
        }
    });
}

/**
 * Update ticket order in the database
 */
function updateTicketOrder(deliverableId, ticketOrder) {
    ajaxRequest({
        url: BASE_URL + "/api/tickets/reorder.php",
        data: { deliverable_id: deliverableId, order: ticketOrder },
        showNotifications: true,
        successMessage: "Tickets reordered successfully."
    });
}

/**
 * Move a ticket to a different deliverable
 */
function moveTicket(ticketId, sourceDeliverableId, targetDeliverableId) {
    ajaxRequest({
        url: BASE_URL + "/api/tickets/update.php",
        data: { ticket_id: ticketId, deliverable_id: targetDeliverableId, action: "move" },
        reloadOnSuccess: true,
        showNotifications: true
    });
}

/**
 * Assign a ticket to a user
 */
function assignTicket(ticketId, userId) {
    ajaxRequest({
        url: BASE_URL + "/api/tickets/assign.php",
        data: { ticket_id: ticketId, assigned_to: userId },
        showNotifications: true,
        successMessage: "Ticket assigned successfully.",
        onSuccess: function() {
            var userCard = $("#project-users .user-card[data-id='" + userId + "']");
            var userName = userCard.find("strong").text();
            $(".ticket-card[data-id='" + ticketId + "'] small").text("Assigned to: " + userName);
        }
    });
}

/**
 * Change a ticket's status
 */
function changeTicketStatus(ticketId, statusId, statusName) {
    ajaxRequest({
        url: BASE_URL + "/api/tickets/change-status.php",
        data: { ticket_id: ticketId, status_id: statusId },
        showNotifications: true,
        successMessage: "Ticket status changed successfully.",
        onSuccess: function() {
            var ticketCard = $(".ticket-card[data-id='" + ticketId + "']");
            var statusBadge = ticketCard.find("[class*='badge-']");
            var oldStatusClass = statusBadge.attr("class").split(" ").find(function(cls) {
                return cls.startsWith("badge-");
            });
            statusBadge.removeClass(oldStatusClass);
            var newStatusClass = "badge-" + statusName.toLowerCase().replace(/ /g, "-");
            statusBadge.addClass(newStatusClass).text(statusName);
        }
    });
}

/**
 * Update deliverable order in the database
 */
function updateDeliverableOrder(deliverableOrder) {
    ajaxRequest({
        url: BASE_URL + "/api/deliverables/reorder.php",
        data: { order: deliverableOrder },
        showNotifications: true,
        successMessage: "Deliverables reordered successfully."
    });
}
