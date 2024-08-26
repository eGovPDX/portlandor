if (typeof GlobalConstants === 'undefined') {
  window.GlobalConstants = class GlobalConstants {
    constructor() {
      this.DEBUG = true;

      this.DEFAULTS = {
        LATITUDE: 45.54,
        LONGITUDE: -122.65,
        ZOOM: 11,
        ZOOM_CLICK: 18,
        BASEMAP_ATTRIBUTION: "PortlandMaps ESRI",
      };

      this.URLS = {
        REVERSE_GEOCODE: 'https://www.portlandmaps.com/api/intersects/?geometry=%7B%20%22x%22:{{x}},%20%22y%22:{{y}},%20%22spatialReference%22:%20%7B%20%22wkid%22:%20%223857%22%7D%20%7D&include=all&detail=1&api_key={{apiKey}}',
        API_BOUNDARY: "https://www.portlandmaps.com/arcgis/rest/services/Public/Boundaries/MapServer/0/query?where=1%3D1&objectIds=35&outFields=*&returnGeometry=true&f=geojson",
        BASEMAP_TILE_LAYER: 'https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}', // New constant
        AERIAL_TILE_LAYER: 'https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer/tile/{z}/{y}/{x}',

      };

      this.LAYER_TYPE = {
        ASSET: 'asset',
        BOUNDARY: 'boundary',
        INCIDENT: 'incident'
      };

      this.LAYER_BEHAVIOR = {
        INFORMATIONAL: 'informational',
        SINGLE_SELECT: 'single_select',
        MULTI_SELECT: 'multi_select'
      };

      this.REVGEOCODE_TYPE = {
        INTERSECTS: 'intersects'
      }

      // Leaflet configuration property objects; don't use uppercase for property names /////////////////////////////

      this.PRIMARY_BOUNDARY_VISIBLE_PROPERTIES = {
        color: 'red',
        fillOpacity: 0,
        weight: 1,
        dashArray: "2 4",
        interactive: false
      }

      this.PRIMARY_BOUNDARY_INVISIBLE_PROPERTIES = {
        color: 'transparent',
        fillOpacity: 0,
        weight: 0,
        interactive: false
      }

      this.DEFAULT_ICON_SIZE = [27, 41];
      this.DEFAULT_ICON_SHADOW_SIZE = [0, 0];
      this.DEFAULT_ICON_ANCHOR = [13, 41];
      this.DEFAULT_ICON_SHADOW_ANCHOR = [0, 0];
      this.DEFAULT_ICON_POPUP_ANCHOR = [0, -41];

      this.DEFAULT_FEATURE_ICON_PROPERTIES = {
        iconUrl: "/modules/custom/portland/modules/portland_webforms/images/map_marker_default.png",
        iconSize: this.DEFAULT_ICON_SIZE,
        shadowSize: this.DEFAULT_ICON_SHADOW_SIZE,
        iconAnchor: this.DEFAULT_ICON_ANCHOR,
        shadowAnchor: this.DEFAULT_ICON_SHADOW_ANCHOR,
        popupAnchor: this.DEFAULT_ICON_POPUP_ANCHOR,
        className: "feature"
      };

      this.DEFAULT_INCIDENT_ICON_PROPERTIES = {
        iconUrl: "/modules/custom/portland/modules/portland_webforms/images/map_marker_incident.png",
        iconSize: this.DEFAULT_ICON_SIZE,
        shadowSize: this.DEFAULT_ICON_SHADOW_SIZE,
        iconAnchor: this.DEFAULT_ICON_ANCHOR,
        shadowAnchor: this.DEFAULT_ICON_SHADOW_ANCHOR,
        popupAnchor: this.DEFAULT_ICON_POPUP_ANCHOR,
        className: "incident"
      };

      this.DEFAULT_SOLVED_ICON_PROPERTIES = {
        iconUrl: "/modules/custom/portland/modules/portland_webforms/images/map_marker_incident_solved.png",
        iconSize: this.DEFAULT_ICON_SIZE,
        shadowSize: this.DEFAULT_ICON_SHADOW_SIZE,
        iconAnchor: this.DEFAULT_ICON_ANCHOR,
        shadowAnchor: this.DEFAULT_ICON_SHADOW_ANCHOR,
        popupAnchor: this.DEFAULT_ICON_POPUP_ANCHOR,
        className: "incident solved"
      };

      this.DEFAULT_ICON_SELECTED_LOCATION = {
        iconUrl: "/modules/custom/portland/modules/portland_webforms/images/map_marker_default_selected.png",
        iconSize: this.DEFAULT_ICON_SIZE,
        shadowSize: this.DEFAULT_ICON_SHADOW_SIZE,
        iconAnchor: this.DEFAULT_ICON_ANCHOR,
        shadowAnchor: this.DEFAULT_ICON_SHADOW_ANCHOR,
        popupAnchor: this.DEFAULT_ICON_POPUP_ANCHOR,
        className: "selected-location"
      };
    }
  }
}
