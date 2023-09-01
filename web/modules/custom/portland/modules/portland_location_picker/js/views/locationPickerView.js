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
      this.statusModal = $('#status_modal');

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
      this.map.on('locationerror', this.handleLocationError);
      this.map.on('locationfound', this.handleLocateMeFound);


      // this.aerialLayer = L.tileLayer(BASEMAP_AERIAL_URL, {
      //   attribution: BASEMAP_AERIAL_ATTRIBUTION
      // });//.addTo(map);

      var aerialControl = this.generateAerialControl();
      aerialControl.addTo(this.map);

      var locateControl = this.generateLocateControl();
      locateControl.addTo(this.map);

    }

    loadMapData() {

    }

    // #region ----- Event handlers -----

    handleLocateButtonClick(e) {
      cancelEventBubble(e);
      locationErrorShown = false;
      this.controller.selfLocateBrowser();
    }

    handleLocateMeFound(e) {
      alert('location found!');
      // if (locCircle) {
      //   map.removeLayer(locCircle);
      // }
      // var radius = e.accuracy;
      // locCircle = L.circle(e.latlng, radius, { weight: 2, fillOpacity: 0.1 }).addTo(map);
      // reverseGeolocate(e.latlng);
      // locateControlContaier.style.backgroundImage = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_locate_on.png")';

      closeStatusModal();
    }

    handleLocationError(e) {
      alert('location error!');
      // var message = e.message;
      // statusModal.dialog('close');
      // showStatusModal(message);
      // locateControlContaier.style.backgroundImage = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_locate.png")';
    }

    // #endregion

    // #region ----- Helper functions -----

    generateLocateControl() {
      const SELF = this;
      var locateControl = L.control({ position: 'bottomright' });
      locateControl.onAdd = function() {
        this.locateControlContainer = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
        this.locateControlContainer.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_locate.png)";
        this.locateControlContainer.title = 'Find my location';
        L.DomEvent.disableClickPropagation(this.locateControlContainer);

        this.locateControlContainer.onclick = function(event) {
          SELF.controller.cancelEventBubble(event);
          //locationErrorShown = false;
          SELF.controller.selfLocateBrowser();
        };
        return this.locateControlContainer;
      };
      return locateControl;
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

    showStatusModal(message) {
      this.statusModal.html('<p class="status-message">' + message + '</p>');
      Drupal.dialog(this.statusModal, {
        width: '600px',
        buttons: [{
          text: 'Close',
          click: function () {
            $(this).dialog('close');
          }
        }]
      }).showModal();
      this.statusModal.removeClass('visually-hidden');
    }

    closeStatusModal() {
      this.statusModal.dialog('close');
    }

      // #endregion

  }

  // Export the view class
  window.LocationPickerView = LocationPickerView;
})(jQuery, Drupal, drupalSettings, L);
