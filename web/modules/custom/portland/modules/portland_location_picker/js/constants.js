const MAP_CONTAINER_ID = "location_map_container";
const DEFAULT_LATITUDE = 45.54;
const DEFAULT_LONGITUDE = -122.65;
const DEFAULT_ZOOM = 11;
const ZOOM_POSITION = 'topright';

const MAP_CONSTANTS = {
  DEFAULT_LATITUDE: 45.54,
  DEFAULT_LONGITUDE: -122.65,
  DEFAULT_ZOOM: 11,
  DEFAULT_ZOOM_CLICK: 18,
}

const MESSAGE_CONSTANTS = {
  OPEN_ISSUE_MESSAGE: "If this issue is what you came here to report, there's no need to report it again.",
  SOLVED_ISSUE_MESSAGE: "This issue was recently solved. If that's not the case, or the issue has reoccured, please submit a new report.",
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

const TICKET_STATUS = {
  NEW: "new",
  OPEN: "open",
  REFERRED: "referred",
  SOLVED: "solved",
  CLOSED: "closed"
}


jQuery.noConflict();
