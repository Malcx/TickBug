/**
 * assets/js/modal.js
 * Custom lightweight modal implementation without Bootstrap
 */

// Modal functionality
(function($) {
  // Add modal method to jQuery
  $.fn.modal = function(action) {
    return this.each(function() {
      if (action === 'show') {
        $(this).fadeIn(300);
        $('body').addClass('modal-open');
        $(this).addClass('show');
      } else if (action === 'hide') {
        $(this).fadeOut(300);
        $('body').removeClass('modal-open');
        $(this).removeClass('show');
      } else if (action === 'toggle') {
        if ($(this).is(':visible')) {
          $(this).modal('hide');
        } else {
          $(this).modal('show');
        }
      }
    });
  };

  // Initialize all modals on the page
  $(document).ready(function() {
    // Close modal when clicking on the close button
    $('.modal .close').on('click', function() {
      $(this).closest('.modal').modal('hide');
    });

    // Close modal when clicking on the modal backdrop
    $('.modal').on('click', function(e) {
      if ($(e.target).hasClass('modal')) {
        $(this).modal('hide');
      }
    });

    // Handle data-dismiss="modal" elements
    $(document).on('click', '[data-dismiss="modal"]', function() {
      $(this).closest('.modal').modal('hide');
    });

    // Handle data-toggle="modal" elements
    $(document).on('click', '[data-toggle="modal"]', function() {
      var targetModal = $($(this).data('target'));
      targetModal.modal('show');
    });
  });
})(jQuery);