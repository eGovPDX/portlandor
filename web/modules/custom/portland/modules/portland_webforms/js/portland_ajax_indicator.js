(function ($, Drupal) {
  Drupal.behaviors.ajaxStatusIndicator = {
    attach: function (context, settings) {
      // Show the loader when an AJAX call starts
      $(document).ajaxStart(function () {
        $('#ajax-status').show(); // Show the loader
      });

      // Hide the loader when the AJAX call completes
      $(document).ajaxStop(function () {
        $('#ajax-status').hide(); // Hide the loader
      });

      // Optionally, handle AJAX errors
      $(document).ajaxError(function () {
        alert('An error occurred while processing the request.');
        $('#ajax-status').hide();
      });
    }
  };
})(jQuery, Drupal);
