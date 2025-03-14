/**
 * assets/js/project-view.js
 * Simplified project view with navigation to dedicated deliverable pages
 */

$(document).ready(function() {
    // Handle deliverable click to navigate to deliverable page
    $(document).on('click', '.deliverable-header', function(e) {
        // Don't navigate if clicking on buttons
        if ($(e.target).hasClass('btn') || $(e.target).closest('.btn').length > 0) {
            return;
        }
        
        const deliverableId = $(this).closest('.deliverable-card').data('id');
        navigateToDeliverablePage(deliverableId);
    });
});

/**
 * Navigate to the deliverable page
 * 
 * @param {number} deliverableId Deliverable ID
 */
function navigateToDeliverablePage(deliverableId) {
    window.location.href = `${BASE_URL}/deliverables.php?id=${deliverableId}`;
}