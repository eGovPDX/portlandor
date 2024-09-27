(function ($, Drupal) {
  Drupal.behaviors.ajaxStatusIndicator = {
    attach: function (context, settings) {
      var $overlay = $('#ajax-overlay');

      // Show the overlay and spinner when an AJAX call starts
      $(document).ajaxStart(function () {
        $overlay.attr('aria-hidden', 'false');  // Make the spinner visible to screen readers
        $('#ajax-status').focus();              // Set focus on the spinner
        $overlay.show();                        // Show the overlay
      });

      // Hide the overlay and spinner when AJAX call completes
      $(document).ajaxStop(function () {
        $overlay.attr('aria-hidden', 'true');   // Hide spinner from screen readers
        $overlay.hide();                        // Hide the overlay
      });

      // Optionally, handle AJAX errors
      $(document).ajaxError(function () {
        alert('An error occurred while processing the request.');
        $overlay.attr('aria-hidden', 'true');   // Hide spinner from screen readers
        $overlay.hide();                        // Hide the overlay
      });
    }
  };
})(jQuery, Drupal);
