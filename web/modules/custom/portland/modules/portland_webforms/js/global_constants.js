// global_constants.js
class GlobalConstants {
  constructor() {
    this.DEFAULTS = {
      LATITUDE: 45.54,
      LONGITUDE: -122.65,
      ZOOM: 11,
      ZOOM_CLICK: 18,
      // ... other constants
    };

    this.URLS = {
      REVERSE_GEOCODE: 'https://www.portlandmaps.com/api/intersects/?geometry=%7B%20%22x%22:${x},%20%22y%22:${y},%20%22spatialReference%22:%20%7B%20%22wkid%22:%20%223857%22%7D%20%7D&include=all&detail=1&api_key=${apiKey}',
      API_BOUNDARY: "https://www.portlandmaps.com/arcgis/rest/services/Public/Boundaries/MapServer/0/query",
      // ... other URLs
    };
  }
}