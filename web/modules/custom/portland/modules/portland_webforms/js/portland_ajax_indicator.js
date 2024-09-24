(function ($, Drupal) {
  Drupal.behaviors.ajaxStatusIndicator = {
    attach: function (context, settings) {
      // Show the overlay and spinner when an AJAX call starts
      $(document).ajaxStart(function () {
        $('#ajax-overlay').show(); // Show the overlay
      });

      // Hide the overlay and spinner when AJAX call completes
      $(document).ajaxStop(function () {
        //$('#ajax-overlay').hide(); // Hide the overlay
      });

      // Optionally, handle AJAX errors
      $(document).ajaxError(function () {
        alert('An error occurred while processing the request.');
        $('#ajax-overlay').hide(); // Hide the overlay on error
      });
    }
  };
})(jQuery, Drupal);
