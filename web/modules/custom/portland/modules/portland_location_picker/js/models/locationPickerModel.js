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
     * Represents a map feature layer. The most common type will be an asset layer that allows assets to
     * be selected, as well as arbitrary map points, so those are set as defaults.
     * // TODO: Do we even need this? Might be better to use Leaflet's layer-from-json functionality.
     */
    static FeatureLayer = class {
      constructor(name, jsonUrl, type = FEATURE_LAYER_DEFAULTS.TYPE,
        behavior = FEATURE_LAYER_DEFAULTS.BEHAVIOR, iconUrl = FEATURE_LAYER_DEFAULTS.ICON_URL,
        iconSelectedUrl = FEATURE_LAYER_DEFAULTS.ICON_SELECTED_URL) {

        this.name = name;
        this.jsonUrl = jsonUrl;
        this.type = type;
        this.behavior = behavior;
        this.iconUrl = iconUrl;
        this.iconSelectedUrl = iconSelectedUrl
        this.features = [];
        this.mapLayers = [];
      }
    }

    /**
     * Represents a map marker object. By default, with only the feature config and latLng,
     * the constructor will instantiate an object that includes a generated asset marker 
     * using the default icon and sizing. The asset marker is referenced using the
     * [object].marker property.
     * 
     * Example:
     *    var marker = new LocationPickerModel.MapMarker(featureConfig, latLng);
     */
    static MapMarker = class {
      constructor(featureConfig, latLng, 
        iconUrl = MAP_MARKER_DEFAULTS.ICON_URL, 
        iconSize = MAP_MARKER_DEFAULTS.ICON_SIZE, 
        shadowSize = MAP_MARKER_DEFAULTS.SHADOW_SIZE, 
        iconAnchor = MAP_MARKER_DEFAULTS.ICON_ANCHOR, 
        shadowAnchor = MAP_MARKER_DEFAULTS.SHADOW_ANCHOR,
        popupAnchor = MAP_MARKER_DEFAULTS.POPUP_ANCHOR,
        draggable = MAP_MARKER_DEFAULTS.DRAGGABLE, 
        riseOnHover = MAP_MARKER_DEFAULTS.RISE_ON_HOVER) {

        this.featureConfig = featureConfig;
        this.latLng = latLng;
        this.iconUrl = iconUrl;
        this.iconSize = iconSize;
        this.shadowSize = shadowSize;
        this.iconAnchor = iconAnchor;
        this.shadowAnchor = shadowAnchor;
        this.popupAnchor = popupAnchor;
        this.draggable = draggable;
        this.riseOnHover = riseOnHover;

        // set properties
        this.className = featureConfig.type;
        
        // can i pass "this" in the constructor?
        this.marker = LocationPickerView.GenerateMarker(latLng, this)

      }
    }

    // ----- END static classes used to create a model of a GeoJSON map layer ----- //
    // #endregion

    // #region ----- Model functions -----

    loadFeatureLayerData(layerConfig, callback) {
      fetch(layerConfig.geojson_url)
        .then((response) => {
          // Check if the response status indicates success (e.g., status code 200)
          if (!response.ok) {
            throw new Error(`HTTP error! Status: ${response.status}`);
          }
          // Parse the response body as JSON
          return response.json();
        })
        .then((jsonData) => {
          // Call the callback function with the parsed JSON data and the layerConfig from the outer scope
          // add layer config data as property of the json object for convenience.
          jsonData.layerConfig = layerConfig;
          var featureLayerDataObj = new LocationPickerModel.FeatureLayer(layerConfig.name, layerConfig.geojson_url, 
            layerConfig.type, layerConfig.behavior, layerConfig.icon_url, layerConfig.icon_url_selected,
            jsonData.features);
            featureLayerDataObj.features = jsonData.features;
          // store this in the model? no, store the rendered feature layer so it can be turned on/off depending on zoom level.
          callback(featureLayerDataObj);
        })
        .catch((error) => {
          // Handle any errors that occurred during the fetch or parsing
          this.controller.view.showErrorModal("Could not load feature layer " + layerConfig.name + "<br><br>" + error);
          console.error('Error fetching JSON:', error);
        });
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
