if (typeof GlobalConstants === 'undefined') {
  window.GlobalConstants = class GlobalConstants {
    constructor() {
      this.DEFAULTS = {
        LATITUDE: 45.54,
        LONGITUDE: -122.65,
        ZOOM: 11,
        ZOOM_CLICK: 18,
        BASEMAP_ATTRIBUTION: "PortlandMaps ESRI",
      };

      this.URLS = {
        REVERSE_GEOCODE: 'https://www.portlandmaps.com/api/intersects/?geometry=%7B%20%22x%22:${x},%20%22y%22:${y},%20%22spatialReference%22:%20%7B%20%22wkid%22:%20%223857%22%7D%20%7D&include=all&detail=1&api_key=${apiKey}',
        API_BOUNDARY: "https://www.portlandmaps.com/arcgis/rest/services/Public/Boundaries/MapServer/0/query",
        BASEMAP_TILE_LAYER: 'https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}', // New constant
        AERIAL_TILE_LAYER: 'https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer/tile/{z}/{y}/{x}',

      };
    }
  }
}
