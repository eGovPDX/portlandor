(function ($, Drupal, drupalSettings, L) {
  /**
   * Represents a Location Picker Model.
   * This model defines the objects used by the location picker.
   * 
   */
  class LocationPickerModel {

    constructor(controller) {
      this.controller = controller;
      // custom form properties
      // this.locationTypes = formSettings.locationTypes;
      this.selectedMarker = drupalSettings.selected_marker;
      this.requireCityLimits = drupalSettings.require_city_limits;
      this.displayCityLimits = drupalSettings.display_city_limits;


      this.locationMap = [];
      this.mapLayers = [];
    }

    // #region ----- Static classes used to create a model of a GeoJSON map layer -----

    /**
     * Represents a GeoJSON feature
     * {
     *   "type": "Feature",
     *   "geometry": {
     *     "type": "Point",
     *     "coordinates": [125.6, 10.1]
     *   },
     *   "properties": {
     *     "name": "Dinagat Islands"
     *   }
     * }
     */
    // TODO: Can we pass a GeoJSON blob to the constructor?
    static MapFeature = class {
      constructor(geometry, properties) {
        this.geometry = geometry;
        this.properties = properties;
      }
    }

    /**
     * Represents a GeoJSON feature geometry.
     */
    static MapGeometry = class {
      constructor(coordinates = [], type = GEOMETRY_TYPES.POINT) {
        this.type = type;
        this.coordinates = coordinates;

        if (this.type != GEOMETRY_TYPES.POINT) {
          console.log('Invalid geometry type. This app currently only supports String.');
        }
      }
    }

    /**
     * Represents a map layer. The most common type will be an asset layer that allows assets to
     * be selected, as well as arbitrary map points, so those are set as defaults.
     * // TODO: Do we even need this? Might be better to use Leaflet's layer-from-json functionality.
     */
    static MapLayer = class {
      constructor(name, features = [], type = LAYER_TYPE.ASSET, behavior = LAYER_BEHAVIOR.SELECTION) {
        this.name = name;
        this.features = features;
        this.type = type;
        this.behavior = behavior;
      }
    }

    // ----- END static classes used to create a model of a GeoJSON map layer ----- //
    // #endregion

    // #region ----- Model functions -----

    loadFeatureLayerData(url, callback) {
      // get layers from config
      var json = '{"type":"FeatureCollection","features":[{"type":"Feature","geometry":{"type":"Point","coordinates":[-122.6544,45.50962]},"properties":{"name":"Graffiti Report","description":"Graffiti Report","id":18236,"status":"open","detail":"<p><em>&quot;Slak&quot; &quot;homie tv&quot; &quot;Sgm&quot; &quot;pxf&quot; &quot;denial&quot; on martial arts business.<\/em><br>\nIncident 18236<br>\nReported 10\/18\/22 6:31 pm<br>\n<\/p>"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[-122.6887866854668,45.53532225336351]},"properties":{"name":"Graffiti Report","description":"Graffiti Report","id":16335,"status":"solved","detail":"<p><em>&quot;lonely days&quot; +blue and white bubble tag<\/em><br>\nIncident 16335<br>\nReported 9\/29\/22 3:09 pm<br>\nUpdated 8\/10\/23 12:00 pm<br>\n<\/p>"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[-122.68618,45.58221]},"properties":{"name":"Graffiti Report","description":"Graffiti Report","id":17122,"status":"solved","detail":"<p><em>smiley face and scribbles.<\/em><br>\nIncident 17122<br>\nReported 10\/6\/22 10:40 am<br>\nUpdated 8\/15\/23 10:44 am<br>\n<\/p>"}}]}';

      callback(json);
    }

    addTodo(text) {
      // this.locationMap.push({ text, completed: false });
    }

    toggleTodo(index) {
      // this.locationMap[index].completed = !this.locationMap[index].completed;
    }

    // #endregion
  }

  // Export the model class
  window.LocationPickerModel = LocationPickerModel;
})(jQuery, Drupal, drupalSettings, L);
