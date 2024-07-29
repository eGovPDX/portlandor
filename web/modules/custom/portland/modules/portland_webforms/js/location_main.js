// main.js
(function ($, Drupal, drupalSettings, L) {
  Drupal.behaviors.portland_location_picker = {
    attach: function (context) {
      console.log("Drupal behavior attach function called");
      $(once('portland-location-picker', '.location-map', context)).each(function (index, element) {
        console.log("Processing element:", index, element);

        const apiKey = drupalSettings.portlandmaps_api_key;
        const settings = drupalSettings.webform.portland_location_widget;

        // Ensure constants are only defined once globally
        if (typeof GlobalConstants === 'undefined') {
          class GlobalConstants {
            constructor() {
              this.DEFAULTS = {
                LATITUDE: 45.54,
                LONGITUDE: -122.65,
                ZOOM: 11,
                ZOOM_CLICK: 18,
              };

              this.URLS = {
                REVERSE_GEOCODE: 'https://www.portlandmaps.com/api/intersects/?geometry=%7B%20%22x%22:${x},%20%22y%22:${y},%20%22spatialReference%22:%20%7B%20%22wkid%22:%20%22',
                API_BOUNDARY: "https://www.portlandmaps.com/arcgis/rest/services/Public/Boundaries/MapServer/0/query",
              };
            }
          }
        }

        let constants = new GlobalConstants();
        let api = new GlobalApi(constants);

        // Ensure each map container has a unique ID
        let containerId = settings.element_id + '_map_container_' + index;
        $(element).attr('id', containerId);

        let location = new LocationWidget(containerId, settings, constants);
        alert(location.message);
      });
    }
  };
})(jQuery, Drupal, drupalSettings, L);
