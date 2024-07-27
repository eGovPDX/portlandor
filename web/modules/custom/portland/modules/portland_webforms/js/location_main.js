// main.js
(function ($, Drupal, drupalSettings, L) {
    Drupal.behaviors.portland_location_picker = {
      attach: function (context) {
        $(once('portland-location-picker', '.location-map', context)).each(function () {

          const apiKey = drupalSettings.portlandmaps_api_key;
          const settings = drupalSettings.webform.portland_location_widget;
  
          const constants = new GlobalConstants();

          const api = new GlobalApi(constants);
          const conatinerId = settings.element_id + '_map_container';
          const location = new LocationWidget(conatinerId, settings, constants);
  
          alert(location.message);
  
          // Example of using the Api module
          // async function getReverseGeocode(lat, lng) {
          //   try {
          //     const data = await Api.reverseGeocode(lat, lng, apiKey);
          //     console.log(data);
          //   } catch (error) {
          //     console.error('Error fetching reverse geocode data:', error);
          //   }
          // }
  
          // Other initialization code, event listeners, etc.
        });
      }
    };
  })(jQuery, Drupal, drupalSettings, L);
  