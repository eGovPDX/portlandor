// Set up handlers for behaviors associated with the `cloudy/search-field` library
(function($, Drupal) {
  'use strict';

  // On pageshow, reset form to restore search field value to original search term
  Drupal.behaviors.search_field = {
    attach: function() {
      jQuery('#search-api-page-block-form')[0].reset()
    }
  };
})(jQuery, Drupal);
