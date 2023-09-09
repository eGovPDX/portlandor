(function ($, Drupal, drupalSettings, L) {
  /**
   * Represents a Location Picker Controller.
   * This controller manages the interaction between the model and view
   * for selecting and displaying locations.
   *
   * @class
   */
  class LocationPickerController {
    /**
     * Creates a new LocationPickerController instance.
     * @constructor
     */
    constructor() {
      //this.formSettings = formSettings;
      this.model = new LocationPickerModel(this);
      this.view = new LocationPickerView(this);

      this.view.initializeMap();

      // load feature layers from element custom configuration.
      // this needs to be done after the map is fully loaded by initializeMap(). if this function is moved
      // out of the controller, a promise may need to be used to ensure the map is fully loaded.
      this.featureLayers = this.loadFeatureLayers();

      // ----- attach event handlers ----- //

      // disable form submit when pressing enter on address field and click Verify button instead
      $('#location_address').on('keydown', function (e) {
        if (e.keyCode == 13) {
          e.preventDefault();
          if (!verifyHidden) {
            $('#location_verify').click();
          }
          return false;
        }
      });
    }

    // ----- utilities ----- //
    
    loadFeatureLayers() {
      var featureLayerConfig = drupalSettings.feature_layers;
      if (featureLayerConfig.length < 1) return false;

      for (var i = 0; i < featureLayerConfig.length; i++) {
        const thisLayerConfig = featureLayerConfig[i];
        this.model.loadFeatureLayerData(thisLayerConfig, (featureLayerDataObj) => {
          // Pass the data to the view for rendering on the map
          this.view.displayFeatureLayer(featureLayerDataObj);
        });
      }
    }

    logError(text) {
      console.log(text);
    }

    getTestMessage(appendedMessage) {
      return MESSAGE_CONSTANTS.OPEN_ISSUE_MESSAGE + " " + appendedMessage;
    }

    selfLocateBrowser() {
      const self = this;
      var t = setTimeout(function () {
        // display status indicator
        self.view.showStatusModal("Triangulating on your current location. Please wait...");
        self.view.map.locate({ watch: false, setView: true, maximumAge: GEOLOCATION_CACHE_MILLISECONDS, enableHighAccuracy: true });
      }, 500);
    }

    cancelEventBubble(event) {
      event.stopPropagation?.(); // Use optional chaining to call stopPropagation if available
      event.cancelBubble = true; // Always set cancelBubble for older browsers
    }

  }

  // Export the controller class
  window.LocationPickerController = LocationPickerController;
})(jQuery, Drupal, drupalSettings, L);
