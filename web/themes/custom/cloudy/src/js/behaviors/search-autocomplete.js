/**
 * @file
 * jQuery autocomplete widget customization for Search API page block search input
 *
 * sets the autocomplete dropdown position relative to the search input
 */
(function ($, Drupal) {
  Drupal.behaviors.cloudySearchAutocomplete = {
    attach: function (context) {
      // find search autocomplete input
      $(once("header-search-autocomplete", "#edit-keys", context)).each(function () {
        $(this).autocomplete({
          // set position relative to element
          position: {
            my: "left top-1",
            at: "left bottom",
            of: "#search-api-page-block-form-search-portland-gov",
            collision: "none",
          },
          appendTo: ".cloudy-search-form",
        });
      });
    },
  };
})(jQuery, Drupal);
