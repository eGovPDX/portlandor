// mapInitializer.js
class MapInitializer {
    constructor(mapElementId, settings) {
      this.mapElementId = mapElementId;
      this.settings = settings;
      this.map = null;
      this.initMap();
    }
  
    initMap() {
      this.map = new L.Map(this.mapElementId, {
        center: new L.LatLng(DEFAULTS.LATITUDE, DEFAULTS.LONGITUDE),
        zoom: DEFAULTS.ZOOM,
        gestureHandling: true,
      });
      // Add layers, controls, and event listeners
      this.addLayers();
      this.addControls();
      this.addEventListeners();
    }
  
    addLayers() {
      const baseLayer = L.tileLayer('https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}', { attribution: "PortlandMaps ESRI" });
      this.map.addLayer(baseLayer);
      // Add other layers
    }
  
    addControls() {
      this.map.addControl(new L.control.zoom({ position: 'topright' }));
      // Add other controls
    }
  
    addEventListeners() {
      this.map.on('click', this.handleMapClick.bind(this));
      // Add other event listeners
    }
  
    handleMapClick(e) {
      const latlng = e.latlng;
      // Handle map click
    }
  }
  