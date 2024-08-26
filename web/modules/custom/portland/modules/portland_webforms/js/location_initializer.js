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
    this.selectedLocation = null;
    this.selectedMarker = null;
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

  // This is the main function for initializing geoJSON layers. It's called for each element of the 
  // layer config array. The layer is rendered using the L.geoJson function. The resulting layer, along
  // with its configuration properties, is stored in a global layers array that can be accessed later.
  initializeLayer(layer) {
    var self = this;
    this.$.ajax({
      url: layer.url,
      success: function (response) {
        var features = response.features;

        switch (layer.type) {
          case self.constants.LAYER_TYPE.ASSET:
            // asset and incident layer types assume features are points.
            self.debug(features.length + " assets found for layer " + layer.name);
            break;

          case self.constants.LAYER_TYPE.INCIDENT:
            // asset and incident layer types assume features are points.

            self.debug(features.length + " incidents found for layer " + layer.name);

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
            newLayer.json = response;
            newLayer.config = layer;
            newLayer.addTo(self.map);
            self.layers.push(newLayer);

            break;

          case self.constants.LAYER_TYPE.BOUNDARY:
            // a boundary layer assumes that the featuers are regions/polygons. they can be used
            // later in Point-in-Polygon (PiP) operations.

            self.debug(features.length + " features found for layer " + layer.name);
            var newLayer = self.L.geoJson(features, layer.boundaryProperties).addTo(self.map);
            if (newLayer.municipality) {
              newLayer.municipality = features[0].properties.CITYNAME;
            }
            newLayer.json = response;
            newLayer.config = layer;
            newLayer.addTo(self.map);
            self.layers.push(newLayer);
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

      this.selectedLocation = this.processGeocodeResult(result, latlng);

      if (!this.selectedLocation) {
        // display error message
        throw new Error("Location could not be determined.");
      }

      // perform layer actions
      for (var i = 0; i < this.layers.length; i++) {
        switch (this.layers[i].config.type) {
          case "boundary":
            // what happens when a boundary layer is clicked? call isLocationValid()
            // - check whether click is within boundary:
            //   - if not within boundary and enforce==true, then return false.
            //   - if within boundary, return region ID from parameter defined in capture_property_path
            var valid = this.isLocationValid(this.layers[i], this.selectedLocation);
            //var layerResult = this.isWithinBoundary(this.layers[i], this.selectedLocation);
            break;

          case "asset":
            // what happens when an asset layer is clicked? we handle the asset being
            // clicked in a handler on the marker. whether we still allow the marker to
            // be set elsewhere on the map is determined by the behavior of the asset 
            // layer, if single_select or multi_select.

            // if any asset layers are 


            this.LAYER_BEHAVIOR = {
              INFORMATIONAL: 'informational',
              SINGLE_SELECT: 'single_select',
              MULTI_SELECT: 'multi_select',
              OPTIONAL_SELECT: 'optional_select'
            };
            // we need to know whether an asset marker is clicked, rather than the layer.
            // marker click will get handled elsewhere, do we just ignore the layer click then?
            break;

          case "incident":
            // similar to asset layer, we really just want to handle when the incident marker
            // is clicked. otherwise, we allow location to be set. 
            break;

          default:
            break;

        }
      }


    } catch (error) {
      this.debug(error.message + " You may have selected a location outside our service area.");
      // TODO: display error message to user in modal dialog
    } finally {
      this.hideLoader();
    }
  }

  setMarker(loation) {
    // remove previous location marker
    if (this.selectedMarker) {
      this.map.removeLayer(this.selectedMarker);
      this.selectedMarker = null;
    }

    var draggable = true;

    // marker icon: this.constants.DEFAULT_ICON_SELECTED_LOCATION
    this.selectedMarker = this.L.marker([this.selectedLocation.lat, this.selectedLocation.lng], {
      icon: this.L.icon(this.constants.DEFAULT_ICON_SELECTED_LOCATION),
      draggable: draggable,
      riseOnHover: true,
      iconSize: this.constants.DEFAULT_ICON_SIZE
    }).addTo(this.map);

    // if address marker is moved, we want to capture the new coordinates
    // locationMarker.off();
    // locationMarker.on('dragend', function (e) {
    //   var latlng = locationMarker.getLatLng();
    //   setLatLngHiddenFields(latlng.lat, latlng.lng);
    //   reverseGeolocate(latlng);
    // });

  }

  // Handles when a boundary layer is clicked.
  // - check whether click is within boundary:
  //   - if not within boundary and enforce==true, then return false.
  //   - if within boundary, return region ID from parameter defined in capture_property_path
  isLocationValid(layer, location) {
    var latlng = L.latLng(location.lat, location.lng);
    var isWithin = leafletPip.pointInLayer(latlng, layer);
    if (isWithin.length < 1 && layer.config.enforce) return false;
    // valid click; get and return region ID. need access to result json (in layer.json).
    return true;
  }

  processGeocodeResult(result, latlng) {
    if (result && result.describe) {
      result.latlng = latlng;
      result.xy = L.Projection.SphericalMercator.project(latlng);
      var location = GlobalUtilities.locationFactory(this.constants.REVGEOCODE_TYPE.INTERSECTS, result);
      this.debug("Location found: " + location.displayAddress);
      this.debug("Lat/Lng: " + location.lat + "," + location.lng);
      return location;
    } else {
      console.error("No data found in reverse geocode result.");
      return null;
    }
  }


  resetMapClick() {
    // rests clicked marker and location fields to prepare for a new map click

    // reset location 
    this.selectedLocation = null;

  }

  showLoader() {
    this.$('.loader-container').css("display", "flex");
  }

  hideLoader() {
    this.$('.loader-container').css("display", "none");
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
