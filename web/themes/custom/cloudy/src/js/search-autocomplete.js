/**
 * @file
 * Global search autocomplete functionality.
 *
 * Sets the position and appends relative to global search form
 */
(function($, Drupal) {
  Drupal.behaviors.cloudySearchAutocomplete = {
    attach: function(context, settings) {
      $(context)
        // Find the header search autocomplete input
        .find("#edit-keys")
        .once("header-search-autocomplete")
        .autocomplete({
          // Set autocomplete position relative to the entire search form width
          position: {
            my: "left top-1",
            at: "left bottom",
            of: "#search-api-page-block-form-search-portland-gov",
            collision: "none"
          },
          appendTo: ".cloudy-search-form"
        });
    }
  };
})(jQuery, Drupal);
