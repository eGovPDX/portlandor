// main.js
(function ($, Drupal, drupalSettings, L) {
  Drupal.behaviors.portland_location_picker = {
    attach: function (context) {
      $(once('portland-location-picker', '.location-map', context)).each(function (index, element) {

        const apiKey = drupalSettings.portlandmaps_api_key;
        const settings = drupalSettings.webform.portland_location_widget;

        let constants = new GlobalConstants();
        let api = new GlobalApi(constants);

        // Ensure each map container has a unique ID
        let containerId = settings.element_id + '_map_container_' + index;
        $(element).attr('id', containerId);

        let location = new LocationWidget(containerId, settings, constants);
        // alert(location.message);
      });
    }
  };
})(jQuery, Drupal, drupalSettings, L);
