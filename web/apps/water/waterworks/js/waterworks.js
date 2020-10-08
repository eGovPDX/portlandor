/* ------------ CONSTANTS ------------ */

const CACHEBUSTER = "?201610281320"
const MAX_MOBILE_WIDTH = 880;
const DEFAULT_LATITUDE = 45.51;
const DEFAULT_LONGITUDE = -122.65;
const DEFAULT_ZOOM = 11;
const ZOOM_POSITION = 'topright';
const ECM_ROOTURL_TEST = '';
const ECM_ROOTURL_PROD = '';
const ROOTURL_PROD = 'https://www.portlandoregon.gov';
const BACK_URL = '/water/waterworks/index.html';
const WATER_ICON = {
    iconUrl: 'img/marker_water.png' + CACHEBUSTER,
    iconSize: [40, 49], // size of the icon
    iconAnchor: [15, 44], // point of the icon which will correspond to marker's location
    popupAnchor: [5, -40] // point from which the popup should open relative to the iconAnchor
};
const WATER_ICON_SELECTED = {
	iconUrl: 'img/marker_water_selected.png' + CACHEBUSTER,
    iconSize: [40, 49], // size of the icon
    iconAnchor: [15, 44], // point of the icon which will correspond to marker's location
    popupAnchor: [5, -40] // point from which the popup should open relative to the iconAnchor
}
const WATER_ICON_GRAY = {
	iconUrl: 'img/marker_water_gray.png' + CACHEBUSTER,
    iconSize: [40, 49], // size of the icon
    iconAnchor: [15, 44], // point of the icon which will correspond to marker's location
    popupAnchor: [5, -40], // point from which the popup should open relative to the iconAnchor
    className: "disabled"
}
const HOME_ICON = {
	iconUrl: 'img/marker_home.png' + CACHEBUSTER,
	iconAnchor:   [17, 30]
}

// consts for options and subfields
// const FIELD_DIRTY_WATER_AREA_TOKEN = "[Dirty Water Area]";
// const FIELD_DIRTY_WATER_REASON_TOKEN = "[Dirty Water Reason]";
// const FIELD_SHUT_DOWN_DATE_TOKEN = "[Shut Down Date]";
// const FIELD_SHUT_DOWN_NUM_RESCUSTOMERS_TOKEN = "[Num Residential and Multifamily Customers]";
// const FIELD_SHUT_DOWN_NUM_BUSCUSTOMERS_TOKEN = "[Num Business Customers]";
// const FIELD_SHUT_DOWN_LOCATION_TOKEN = "[Shut Down Location]";
// const FIELD_SHUT_DOWN_REASON_TOKEN = "[Shut Down Reason]";
// const FIELD_SHUT_DOWN_DURATION_TOKEN = "[Shut Down Duration]";
// const FIELD_SHUT_DOWN_DURATION_HOURS_TOKEN = "[Shut Down Duration:Hours]";
// const FIELD_SHUT_DOWN_DURATION_START_TOKEN = "[Shut Down Duration:Start]";
// const FIELD_SHUT_DOWN_DURATION_END_TOKEN = "[Shut Down Duration:End]";
// const FIELD_SHUT_DOWN_DURATION_DATE_TOKEN = "[Shut Down Duration:Date]";
// const FIELD_MAIN_BREAK_LOCATION_TOKEN = "[Break Location]";
// const FIELD_MAIN_BREAK_COMPLETION_DATE_TOKEN = "[Expected Completion Date]";
// const FIELD_MAIN_BREAK_IMPACTED_STREET_TOKEN = "[Impacted Traffic Street]";
// const FIELD_MAIN_BREAK_NUMBER_HOMES_TOKEN = "[Number of Homes]";
// const FIELD_MAIN_BREAK_STREET_NAME_TOKEN = "[Street Name]";
// const FIELD_MAIN_BREAK_PIPE_SIZE_TOKEN = "[Pipe Size]";
// const FIELD_MAIN_BREAK_PIPE_MATERIAL_TOKEN = "[Pipe Material]";
// const FIELD_MAIN_BREAK_PIPE_YEAR_TOKEN = "[Pipe Year]";
// const FIELD_MAIN_FLUSHING_NEIGHBORHOOD_TOKEN = "[Impacted Neighborhood]";
// const FIELD_MAIN_FLUSHING_SEASON_TOKEN = "[Season]";
// const FIELD_MAIN_FLUSHING_YEAR_TOKEN = "[Year]";
// const FIELD_MAIN_FLUSHING_INTERSECTION_TOKEN = "[Flushing Neighborhood or Intersection]";
// const FIELD_MAIN_FLUSHING_DATE_TOKEN = "[Flushing Date]";
// const FIELD_PUBLIC_INFO_PHONE = "[Public Information Phone Number]";


/* ------------- GLOBALS ------------ */

// isMobileView is used to modify the behavior of the app on smaller screens.
// at a certain width/breakpoint, the sidebar is always displayed and marker popups are not.
// we determine whether mobile view by testing a specific CSS style that changes at the same breakpoint.
// whenever the screen is resized, we want to recalculate this.
var isMobileView = calculateIsMobileView();
// by default use prod ECM url, unless testDev querystring is true
var forceDev = window.location.href.indexOf("testDev=true") > 0;
var ecm_url = forceDev || window.location.href.indexOf(ROOTURL_PROD) == -1 ? ECM_ROOTURL_TEST : ECM_ROOTURL_PROD;


/* ----------------- angular init -------------- */

// waterworks Angular app init ///////////////////////////
var app = angular.module('waterworks', ['ui.bootstrap', 'geolocation', 'ngSanitize']);

app.config(function($sceDelegateProvider) {
  $sceDelegateProvider.resourceUrlWhitelist([
    'self',
    'http*://*.lndo.site/**',
    'http*://*portlandor.pantheonsite.io/**',
    'http*://*.portland.gov/**'
  ]);
});


/* ------------- helper functions ------------ */

function calculateIsMobileView() {
	return $(window).width() < MAX_MOBILE_WIDTH;
}

function htmlDecode(input) {
  var e = document.createElement('div');
  e.innerHTML = input;
  return e.childNodes.length === 0 ? "" : e.childNodes[0].nodeValue;
}

String.prototype.endsWith = function(suffix) {
    return this.indexOf(suffix, this.length - suffix.length) !== -1;
};

// https://tc39.github.io/ecma262/#sec-array.prototype.find
if (!Array.prototype.find) {
  Object.defineProperty(Array.prototype, 'find', {
    value: function(predicate) {
     // 1. Let O be ? ToObject(this value).
      if (this == null) {
        throw new TypeError('"this" is null or not defined');
      }

      var o = Object(this);

      // 2. Let len be ? ToLength(? Get(O, "length")).
      var len = o.length >>> 0;

      // 3. If IsCallable(predicate) is false, throw a TypeError exception.
      if (typeof predicate !== 'function') {
        throw new TypeError('predicate must be a function');
      }

      // 4. If thisArg was supplied, let T be thisArg; else let T be undefined.
      var thisArg = arguments[1];

      // 5. Let k be 0.
      var k = 0;

      // 6. Repeat, while k < len
      while (k < len) {
        // a. Let Pk be ! ToString(k).
        // b. Let kValue be ? Get(O, Pk).
        // c. Let testResult be ToBoolean(? Call(predicate, T, « kValue, k, O »)).
        // d. If testResult is true, return kValue.
        var kValue = o[k];
        if (predicate.call(thisArg, kValue, k, o)) {
          return kValue;
        }
        // e. Increase k by 1.
        k++;
      }

      // 7. Return undefined.
      return undefined;
    }
  });
}