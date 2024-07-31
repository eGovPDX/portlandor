class LocationWidget {
  constructor(mapElementId, settings, constants) {
    this.mapElementId = mapElementId;
    this.settings = settings;
    this.constants = constants;
    this.map = null;
    this.locationErrorShown = false;  // Declare and initialize the instance property
    this.currentView = "base";  // Also declare currentView as an instance property
    this.baseLayer = null;  // Declare baseLayer as an instance property
    this.aerialLayer = null;  // Declare aerialLayer as an instance property
    this.initMap();
  }

  initMap() {
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
    this.baseLayer = L.tileLayer(this.constants.URLS.BASEMAP_TILE_LAYER, {
      attribution: this.constants.DEFAULTS.BASEMAP_ATTRIBUTION
    });
    this.map.addLayer(this.baseLayer);
    
    // Define aerial layer (replace with the actual URL or logic)
    this.aerialLayer = L.tileLayer(this.constants.URLS.AERIAL_TILE_LAYER, {
      attribution: this.constants.DEFAULTS.BASEMAP_ATTRIBUTION
    });
    
    // Add other layers if needed
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
    this.map.on('click', this.handleMapClick.bind(this));
    // Add other event listeners
  }

  handleMapClick(e) {
    const latlng = e.latlng;
    // Handle map click
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
