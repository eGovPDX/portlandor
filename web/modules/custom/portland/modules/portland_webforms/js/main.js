var LocationWidget = LocationWidget || {};

(function ($, Drupal, drupalSettings, L, LocationWidget) {

  var initialized = false;
  var map;

  // Here's how to reverse geolocate a park. Note the x/y values in the geometry parameter:
  // https://www.portlandmaps.com/arcgis/rest/services/Public/Parks_Misc/MapServer/2/query?geometry=%7B%22x%22%3A-122.55203425884248%2C%22y%22%3A45.53377174783918%2C%22spatialReference%22%3A%7B%22wkid%22%3A4326%7D%7D&geometryType=esriGeometryPoint&spacialRel=esriSpatialRelIntersects&returnGeometry=false&returnTrueCurves=false&returnIdsOnly=false&returnCountOnly=false&returnDistinctValues=false&f=pjson"
  // returns an object that includes the park name.

  /**
   * Attach the machine-readable name form element behavior.
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   *   Attaches machine-name behaviors.
   */
  Drupal.behaviors.locationWidgetBehavior = {
    attach: function (context, settings) {

      LocationWidget.Core.init();
      LocationWidget.Layers.init();

      // $(once('portland_location_widget', 'fieldset.portland-location-widget--wrapper', context)).each(function () {
      //   alert("Location Widget is plugged in!");
      // });
    }
  };
})(jQuery, Drupal, drupalSettings, L, LocationWidget);
