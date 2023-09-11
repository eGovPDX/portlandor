const MAP_CONTAINER_ID = "location_map_container";
const DEFAULT_LATITUDE = 45.54;
const DEFAULT_LONGITUDE = -122.65;
const DEFAULT_ZOOM = 11;
const ZOOM_POSITION = 'topright';
const BASEMAP_DEFAULT_URL = "https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete/MapServer/tile/{z}/{y}/{x}";
const BASEMAP_DEFAULT_ATTRIBUTION = "PortlandMaps ESRI";
const BASEMAP_AERIAL_URL = "https://www.portlandmaps.com/arcgis/rest/services/Public/Basemap_Color_Complete_Aerial/MapServer/tile/{z}/{y}/{x}";
const BASEMAP_AERIAL_ATTRIBUTION = "PortlandMaps ESRI";
const GEOLOCATION_CACHE_MILLISECONDS = 0;
const DEFAULT_ICON_SIZE = [27, 41];

const MAP_CONSTANTS = {
  DEFAULT_LATITUDE: 45.54,
  DEFAULT_LONGITUDE: -122.65,
  DEFAULT_ZOOM: 11,
  DEFAULT_ZOOM_CLICK: 18,
}

const MESSAGE_CONSTANTS = {
  OPEN_ISSUE_MESSAGE: "If this issue is what you came here to report, there's no need to report it again.",
  SOLVED_ISSUE_MESSAGE: "This issue was recently solved. If that's not the case, or the issue has reoccured, please submit a new report.",
  LOCATION_ACCURACY: "WARNING: Location accuracy is low. Please zoom in and refine your location",
  TRY_AGAIN: "Please try again. If the error persists, please <a href=\"/feedback\">contact us</a>.",
}

const GEOMETRY_TYPES = {
  POINT: "Point", 
  LINESTRING: "LineString", 
  POLYGON: "Polygon", 
  MULTIPOINT: "MultiPoint", 
  MULTILINESTRING: "MultiLineString", 
  MULTIPOLYGON: "MultiPolygon"
}

const LAYER_TYPE = {
  ASSET: "asset",
  INCIDENT: "incident",
  REGION: "region"
}

const LAYER_BEHAVIOR = {
  SELECTION: "selection",
  SELECTIONONLY: "selection-only",
  INFORMATION: "information",
  GEOFENCE: "geofence"
}

const FEATURE_LAYER_DEFAULTS = {
  TYPE: LAYER_TYPE.ASSET,
  BEHAVIOR: LAYER_BEHAVIOR.SELECTION,
  ICON_URL: '/modules/custom/portland/modules/portland_location_picker/images/map_marker_default.png',
  ICON_SELECTED_URL: '/modules/custom/portland/modules/portland_location_picker/images/map_marker_default_selected.png',
  INCIDENT_ICON_URL: '/modules/custom/portland/modules/portland_location_picker/images/map_marker_incident.png',
  INCIDENT_SOLVED_ICON_URL: '/modules/custom/portland/modules/portland_location_picker/images/map_marker_incident_solved.png',
  VISIBLE_ZOOM: 18
}

const MAP_MARKER_DEFAULTS = {
  ICON_URL: FEATURE_LAYER_DEFAULTS.ICON_URL,
  ICON_SIZE: [27, 41],
  SHADOW_SIZE: [0, 0],
  ICON_ANCHOR: [13, 41],
  SHADOW_ANCHOR: [0, 0],
  POPUP_ANCHOR: [0, -41],
  DRAGGABLE: false,
  RISE_ON_HOVER: true,
}

const TICKET_STATUS = {
  NEW: "new",
  OPEN: "open",
  REFERRED: "referred",
  SOLVED: "solved",
  CLOSED: "closed"
}


jQuery.noConflict();
