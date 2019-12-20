import $ from 'jquery';
import Drupal from 'Drupal';

// Set up handlers for behaviors associated with the `cloudy/search-field` library
// On pageshow, reset form to restore search field value to original search term
Drupal.behaviors.search_field = {
  attach: function() {
    $('#search-api-page-block-form')[0].reset()
  }
};
