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
      this.mapLayers = [];
      this.locationCircle;
      this.locationMarker;
      this.defaultSelectedMarkerIcon;
      this.aerialControlContainer;
      this.locateControlContainer;
      this.currentView = "base";
      this.statusModal = $('#status_modal');

      // this.mapReadyPromise = new Promise((resolve) => {
      //   this.initializeMap();
      //   this.map.on('load', () => {
      //     resolve(this.map);
      //   })
      // });

      // this.loadMapData();


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

      // add base layer and controls to map
      this.map.addLayer(this.baseLayer);
      this.map.addControl(new L.control.zoom({ position: ZOOM_POSITION }));
      this.map.addControl(this.generateAerialControl());
      this.map.addControl(this.generateLocateControl());

      // add event handlers to map
      this.map.on('locationerror', this.handleLocationError.bind(this));
      this.map.on('locationfound', this.handleLocateMeFound.bind(this));
      this.map.on('zoomend', this.handleZoomEnd.bind(this));

      this.defaultSelectedMarkerIcon = L.icon({
        iconUrl: drupalSettings.selected_marker,
        iconSize: DEFAULT_ICON_SIZE, // size of the icon
        shadowSize: [0, 0], // size of the shadow
        iconAnchor: [13, 41], // point of the icon which will correspond to marker's location
        shadowAnchor: [0, 0],  // the same for the shadow
        popupAnchor: [0, -41]
      });


    }

    // #region ----- Event handlers -----

    handleLocateButtonClick(e) {
      cancelEventBubble(e);
      locationErrorShown = false;
      this.controller.selfLocateBrowser();
    }

    handleLocateMeFound(e) {
      if (this.locationCircle) {
        this.map.removeLayer(this.locationCircle);
      }
      var radius = e.accuracy;
      var roundedRadius = Math.round(radius * 3.28084);
      this.locationCircle = L.circle(e.latlng, radius, { weight: 2, fillOpacity: 0.1 }).addTo(this.map);
      
      if (radius > 100) {
        var message = MESSAGE_CONSTANTS.LOCATION_ACCURACY;
        this.closeStatusModal();
        this.showStatusModal(message);
      } else {
        this.setMarker(e.latlng);
        this.locateControlContainer.style.backgroundImage = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_locate_on.png")';
        this.closeStatusModal();
      }

      
      // adjust map view to fit accuracy circle
      var bounds = this.locationCircle.getBounds();
      this.map.fitBounds(bounds);
    }

    handleLocationError(e) {
      this.closeStatusModal();
      this.showErrorModal(e.message);
      this.locateControlContainer.style.backgroundImage = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_locate.png")';
    }

    handleZoomEnd(e) {
      this.map.closePopup();
      var currentZoom = this.map.getZoom();

      // spin through loaded layers and determine whether or not to show each
      for (var i = 0; i < this.mapLayers.length; i++) {
        // if current zoom is greater than or equal to zoom_level, show the layer
        if (currentZoom >= this.mapLayers[i].visibleZoom) {
          this.map.addLayer(this.mapLayers[i]);
        } else {
          this.map.removeLayer(this.mapLayers[i]);
        }
      }
    }

    // #endregion

    // #region ----- Helper functions -----

    /**
     * Generates a feature layer. The layer gets shown immediately if the map is at the appropriate
     * zoom level. Otherwise, it's shown using the zoomend event handler when the appropriate zoom
     * level is reached. We don't want to overload the map when zoomed out.
     * TODO: filter the feature layer by extent.
     *  
     * @param {*} featureLayerData - geoJson that includes features and layer config
     */
    loadFeatureLayer(layerData) {
      console.log(layerData.name + " features found: " + layerData.features.length);
      
      var markerIcon = LocationPickerView.GenerateMarkerIcon(layerData);

      var layer = L.geoJson(layerData.features, {
        coordsToLatLng: function (coords) {
          // need to reverse the coords for Leaflet
          return new L.LatLng(coords[1], coords[0]);
        },
        pointToLayer: function (feature, latlng) {
          // need to create a marker for every feature.
          //var markerObj = new LocationPickerModel.MapMarker(layerData, feature);
          return LocationPickerView.GenerateMarker(latlng, markerIcon);
        }
      });

      // store visible zoom value as custom property on layer
      layer.visibleZoom = layerData.visibleZoom;

      // add to global array for use later
      this.mapLayers.push(layer);

      // add layer to map if we're at the right zoom level; otherwise, add it in the zoomend event handler.
      if (this.map.getZoom() >= layerData.visibleZoom) {
        layer.addTo(this.map);
      }
    }

    generateLocateControl() {
      const SELF = this;
      var locateControl = L.control({ position: 'bottomright' });
      locateControl.onAdd = function() {
        SELF.locateControlContainer = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
        SELF.locateControlContainer.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_locate.png)";
        SELF.locateControlContainer.title = 'Find my location';
        L.DomEvent.disableClickPropagation(SELF.locateControlContainer);

        SELF.locateControlContainer.onclick = function(event) {
          SELF.controller.cancelEventBubble(event);
          SELF.controller.selfLocateBrowser();
        };
        return SELF.locateControlContainer;
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

    showErrorModal(message) {
      message = message + '<br><br>' + MESSAGE_CONSTANTS.TRY_AGAIN;
      this.showStatusModal(message);
    }

    setMarker(latLng) {
      if (this.locationMarker) {
        this.map.removeLayer(this.locationMarker);
        this.locationMarker = null;
      }

      // set lat/lng fields
      this.locationMarker = L.marker(latLng, { icon: this.defaultSelectedMarkerIcon, draggable: true, riseOnHover: true, iconSize: DEFAULT_ICON_SIZE }).addTo(this.map);

      // if address marker is moved, we want to capture the new coordinates
      this.locationMarker.off();
      this.locationMarker.on('dragend', function (e) {
        var latlng = this.locationMarker.getLatLng();
        //setLatLngHiddenFields(latlng.lat, latlng.lng);
        //reverseGeolocate(latlng);
      });


    }

    /**
     * Generates and returns a Leaflet marker object.
     * 
     * @param {MapMarker} mapMarker 
     * @returns L.marker
     */
    static GenerateMarker(latLng, markerIcon, 
      draggable = MAP_MARKER_DEFAULTS.DRAGGABLE,
      riseOnHover = MAP_MARKER_DEFAULTS.RISE_ON_HOVER) {

        return L.marker(latLng, { 
        icon: markerIcon, 
        draggable: draggable, 
        riseOnHover: riseOnHover 
      });
    }

    static GenerateMarkerIcon(layerData,
      iconUrl = LocationPickerView.PickIcon(layerData), 
      iconSize = MAP_MARKER_DEFAULTS.ICON_SIZE, 
      shadowSize = MAP_MARKER_DEFAULTS.SHADOW_SIZE, 
      iconAnchor = MAP_MARKER_DEFAULTS.ICON_ANCHOR, 
      shadowAnchor = MAP_MARKER_DEFAULTS.SHADOW_ANCHOR,
      popupAnchor = MAP_MARKER_DEFAULTS.POPUP_ANCHOR) {

      return L.icon({
        iconUrl: iconUrl,
        iconSize: iconSize,
        shadowSize: shadowSize,
        iconAnchor: iconAnchor,
        shadowAnchor: shadowAnchor,
        popupAnchor: popupAnchor,
        className: layerData.type
      });
    }

    static PickIcon(layerData) {
      if (layerData.type == LAYER_TYPE.ASSET) {
        return FEATURE_LAYER_DEFAULTS.ICON_URL;
      }
      if (layerData.type == LAYER_TYPE.INCIDENT) {
        return FEATURE_LAYER_DEFAULTS.INCIDENT_ICON_URL;
      }
    }

    // #endregion

  }

  // Export the view class
  window.LocationPickerView = LocationPickerView;
})(jQuery, Drupal, drupalSettings, L);
