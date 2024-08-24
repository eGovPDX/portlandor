class LocationWidget {
  constructor(mapElementId, settings, constants, $, L) {
    this.mapElementId = mapElementId;
    this.settings = settings;
    this.constants = constants;
    this.map = null;
    this.locationErrorShown = false;  // Declare and initialize the instance property
    this.currentView = "base";  // Also declare currentView as an instance property
    this.baseLayer = null;  // Declare baseLayer as an instance property
    this.aerialLayer = null;  // Declare aerialLayer as an instance property
    this.layersConfig = this.settings.layers; // array of layer configuration data
    this.layers = []; // array to hold data about the map layers (incidents, boundaries, assets, etc.)
    this.primaryBoundaryConfig = this.settings.primary_boundary;
    this.$ = $;
    this.L = L;
    this.api = new GlobalApi(constants);
    this.initMap();
  }

  initMap() {
    this.debug("Initializing map " + this.settings.element_id);
    this.map = new L.Map(this.mapElementId, {
      center: new L.LatLng(this.constants.DEFAULTS.LATITUDE, this.constants.DEFAULTS.LONGITUDE),
      zoom: this.constants.DEFAULTS.ZOOM,
      gestureHandling: true,
      zoomControl: false
    });
    // Add layers, controls, and event listeners
    this.addLayers();
    this.addControls();
    this.addEventListeners();
    this.message = "It's plugged in!";
  }

  addLayers() {
    var self = this;

    this.baseLayer = L.tileLayer(this.constants.URLS.BASEMAP_TILE_LAYER, {
      attribution: this.constants.DEFAULTS.BASEMAP_ATTRIBUTION
    });
    this.map.addLayer(this.baseLayer);
    //this.map.on('click', this.handleMapClick);

    // this.addEventListeners();

    // Define aerial layer (replace with the actual URL or logic)
    this.aerialLayer = L.tileLayer(this.constants.URLS.AERIAL_TILE_LAYER, {
      attribution: this.constants.DEFAULTS.BASEMAP_ATTRIBUTION
    });

    // Add primary boundary layer
    this.initializeLayer({
      name: self.settings.primary_boundary.name || "Primary Boundary Layer",
      url: self.settings.primary_boundary.url || this.constants.URLS.API_BOUNDARY,
      type: this.constants.LAYER_TYPE.BOUNDARY,
      behavior: this.constants.LAYER_BEHAVIOR.INFORMATIONAL,
      visible: self.settings.primary_boundary.visible === false ? false : true,
      enforce: self.settings.primary_boundary.enforce === false ? false : true,
      boundaryProperties: self.settings.primary_boundary.visible === false ? this.constants.PRIMARY_BOUNDARY_INVISIBLE_PROPERTIES : this.constants.PRIMARY_BOUNDARY_VISIBLE_PROPERTIES
    });

    // Add configured layers
    for (var i = 0; i < this.layersConfig.length; i++) {
      this.initializeLayer(this.layersConfig[i]);
    }
  }

  debug(message) {
    if (this.constants.DEBUG) GlobalUtilities.debug(message);
  }

  initializeLayer(layer) {
    var self = this;
    this.$.ajax({
      url: layer.url,
      success: function (response) {
        var features = response.features;

        switch (layer.type) {
          case self.constants.LAYER_TYPE.ASSET:
            self.debug(features.length + " assets found for layer "  + layer.name);
            break;

          case self.constants.LAYER_TYPE.INCIDENT:
            self.debug(features.length + " incidents found for layer "  + layer.name);

            var newLayer = L.geoJson(features, {
              coordsToLatLng: function (coords) {
                return new L.LatLng(coords[1], coords[0]);
              },
              pointToLayer: function (feature, latlng) {
                var icon = L.icon(self.constants.DEFAULT_FEATURE_ICON_PROPERTIES);
                return L.marker(latlng, {
                  icon: icon,
                  draggable: false,
                  riseOnHover: true,
                  iconSize: self.constants.DEFAULT_ICON_SIZE
                });
              },
              onEachFeature: function (feature, layer) {
                self.debug(feature);
              },
              interactive: false
            });
            newLayer.addTo(self.map);
            self.layers.push(newLayer);
  
            break;

          case self.constants.LAYER_TYPE.BOUNDARY:
            self.debug(features.length + " features found for layer "  + layer.name);
            var boundaryLayer = self.L.geoJson(features, layer.boundaryProperties).addTo(self.map);
            if (boundaryLayer.municipality) {
              boundaryLayer.municipality = features[0].properties.CITYNAME;
            }
            break;

          default:
            self.debug("Invalid layer type: \"" + layer.type + "\"");
        }

        self.debug(layer.name + " fully loaded.");
      },
      error: function (e) {
        console.error(e);
        // TODO: display error message to user
      }
    })

  }

  addControls() {
    // zoom control /////////////////////
    this.map.addControl(new L.control.zoom({ position: 'topright' }));

    // aerial view control //////////////
    const AerialViewControl = L.Control.extend({
      options: {
        position: "bottomright"
      },
      onAdd: function (map) {
        const aerialViewControlContainer = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
        aerialViewControlContainer.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png)";
        aerialViewControlContainer.title = 'Aerial view';
        aerialViewControlContainer.onclick = (e) => this.handleAerialButtonClick(e);  // Corrected typo here
        return aerialViewControlContainer;
      }.bind(this)
    });
    this.map.addControl(new AerialViewControl());
  }

  addEventListeners() {

    // binding "this" to the handler call, so that it's available in the functions
    this.map.on('click', this.handleMapClick.bind(this));
    // Add other event listeners
  }

  async handleMapClick(e) {

    this.showLoader();
    this.resetMapClick();
    const latlng = e.latlng;
    // TODO: RESET MAP CLICK:
    // - show ajax loader
    // - reset clicked marker
    // - clear location fields

    // Call the reverseGeocode function and process the result
    try {
      const result = await this.api.reverseGeocode(latlng, this.settings.apiKey);
      this.processGeocodeResult(result, latlng); // Function to handle the API response
    } catch (error) {
      console.error("Reverse geocode failed:", error);
    } finally {
      this.hideLoader();
    }
  }

  processGeocodeResult(result, latlng) {
    if (result && result.describe) {
      result.latlng = latlng;
      result.xy = L.Projection.SphericalMercator.project(latlng);
      var location = GlobalUtilities.locationFactory("intersects", result);
      this.debug("Location found: " + location.displayAddress);
      this.debug("Lat/Lng: " + location.lat + "," + location.lng);
    } else {
      console.error("No data found in reverse geocode result.");
    }
  }


  resetMapClick() {
    // rests clicked marker and location fields to prepare for a new map click
  }

  showLoader() {
    this.$('.loader-container').css("display","flex");
  }

  hideLoader() {
    this.$('.loader-container').css("display","none");
  }

  handleSelfLocateButtonClick() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(position => {
        const latlng = new L.LatLng(position.coords.latitude, position.coords.longitude);
        this.map.setView(latlng, this.constants.DEFAULTS.ZOOM_CLICK);
        L.marker(latlng).addTo(this.map)
          .bindPopup("You are here!")
          .openPopup();
      }, error => {
        console.error("Geolocation error:", error);
      });
    } else {
      alert("Geolocation is not supported by this browser.");
    }
  }

  handleAerialButtonClick(e) {
    this.locationErrorShown = false;  // Use the instance property

    if (this.currentView != "aerial") {
      this.map.removeLayer(this.baseLayer);
      this.map.addLayer(this.aerialLayer);
      this.currentView = "aerial";
      // show icon active
      e.target.style.background = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_base.png")';
    } else {
      this.map.removeLayer(this.aerialLayer);
      this.map.addLayer(this.baseLayer);
      this.currentView = "base";
      e.target.style.background = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png")';
    }
  }

  cancelEventBubble(e) {
    var evt = e ? e : window.event;
    if (evt.stopPropagation) evt.stopPropagation();
    if (evt.cancelBubble != null) evt.cancelBubble = true;
  }
}
