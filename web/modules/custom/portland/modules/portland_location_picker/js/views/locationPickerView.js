(function ($, Drupal, drupalSettings, L) {
  /**
   * Represents a Location Picker View.
   * This model generates and manipulates the user interface for the location picker.
   * 
   */
  class LocationPickerView {

    constructor(controller) {
      this.controller = controller;
      this.baseLayer = L.tileLayer(BASEMAP_DEFAULT_URL, { attribution: BASEMAP_DEFAULT_ATTRIBUTION });
      this.aerialLayer = L.tileLayer(BASEMAP_AERIAL_URL, { attribution: BASEMAP_AERIAL_ATTRIBUTION });
      this.map;
      this.aerialControlContainer;
      this.locateControlContaier;
      this.currentView = "base";

      this.initializeMap();


    }

    /**
     * Initializes the map object directly into this.map
     * 
     */
    initializeMap() {

      // look for map container
      // TODO: add support for more than one map;
      var container = $('#location_map_container');
      if (container.length < 1) {
        this.controller.logError("Map container not found.");
        return false;
      }

      this.map = new L.Map(MAP_CONTAINER_ID, {
        center: new L.LatLng(DEFAULT_LATITUDE, DEFAULT_LONGITUDE),
        zoomControl: false,
        zoom: DEFAULT_ZOOM,
        gestureHandling: true
      });
      this.map.addLayer(this.baseLayer);
      this.map.addControl(new L.control.zoom({ position: ZOOM_POSITION }));

      // this.aerialLayer = L.tileLayer(BASEMAP_AERIAL_URL, {
      //   attribution: BASEMAP_AERIAL_ATTRIBUTION
      // });//.addTo(map);

      var aerialControl = this.generateAerialControl();
      aerialControl.addTo(this.map);

      this.initLocateControl();
      //locateControl.addTo(this.map);


      // aerialControl.onAdd = function (map) {

      //   this.aerialControlContainer = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
      //   this.aerialControlContainer.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png)";
      //   this.aerialControlContainer.title = 'Toggle aerial view';
      //   L.DomEvent.disableClickPropagation(this.aerialControlContainer);

      //   this.aerialControlContainer.onclick = function(e) {
      //     if (SELF.currentView == "aerial") {
      //       SELF.map.removeLayer(SELF.aerialLayer);
      //       SELF.currentView = "base";
      //       e.currentTarget.style.background = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png")';
      //     } else {
      //       SELF.map.addLayer(SELF.aerialLayer);
      //       SELF.currentView = "aerial";
      //       e.currentTarget.style.background = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_base.png")';
      //     }
      //   };
      //   return this.aerialControlContainer;
      // };

    }

    loadMapData() {

    }

    initLocateControl() {
      const SELF = this;
      var locateControl = L.control({ position: 'botomright' });
      locateControl.addTo(this.map);;
      locateControl.onAdd = function() {
        SELF.locateControlContaier = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
        SELF.locateControlContaier.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_locate.png)";
        SELF.locateControlContaier.title = 'Locate Me';
        //locateControlContaier.onclick = handleLocateButtonClick;
        return SELF.locateControlContaier;
      };
    }

    generateAerialControl() {
      const SELF = this;
      var aerialControl = L.control({ position: 'bottomright' });
      aerialControl.onAdd = function() {
        this.aerialControlContainer = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
        this.aerialControlContainer.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png)";
        this.aerialControlContainer.title = 'Toggle aerial view';
        L.DomEvent.disableClickPropagation(this.aerialControlContainer);

        this.aerialControlContainer.onclick = function(e) {
          if (SELF.currentView == "aerial") {
            SELF.map.removeLayer(SELF.aerialLayer);
            SELF.currentView = "base";
            e.currentTarget.style.background = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png")';
          } else {
            SELF.map.addLayer(SELF.aerialLayer);
            SELF.currentView = "aerial";
            e.currentTarget.style.background = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_base.png")';
          }
        };
        return this.aerialControlContainer;
      };
      return aerialControl;
    }

    handleAerialButtonClick(e) {
      cancelEventBubble(e);
      locationErrorShown = false;
      //toggleAerialView();
      alert('toggle view');
    }


  }

  // Export the view class
  window.LocationPickerView = LocationPickerView;
})(jQuery, Drupal, drupalSettings, L);
