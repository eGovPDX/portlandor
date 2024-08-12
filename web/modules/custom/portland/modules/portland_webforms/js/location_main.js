// main.js
(function ($, Drupal, drupalSettings, L) {
  Drupal.behaviors.portland_location_picker = {
    attach: function (context) {
      $(once('portland-location-picker', '.location-map', context)).each(function (index, element) {

        const elementId = $(element).attr('id'); // this should match what's on line 25 of PortlandLocationWidget.php

        const settings = drupalSettings.webform.portland_location_widget[elementId];
        
        const apiKey = drupalSettings.portlandmaps_api_key;
        settings.apiKey = apiKey;

        // TODO: review scoping of variables
        let constants = new GlobalConstants();
        let api = new GlobalApi(constants);

        // Ensure each map container has a unique ID // TODO: Do we need this?
        let containerId = settings.id;// + '_map_container_' + index;
        $(element).attr('id', containerId);

        let location = new LocationWidget(containerId, settings, constants, $, L);
        // alert(location.message);
      });
    }
  };
})(jQuery, Drupal, drupalSettings, L);
