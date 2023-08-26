/**
 * Represents a location on a Leaflet map.
 */
class Location {
  constructor(lat, lon) {
    this.lat = lat;
    this.lon = lon;
  }
}

/**
 * Represents a Location Picker Model.
 * This model defines the objects used by the location picker.
 * 
 * Map
 * 
 *
 * @class
 */
class LocationPickerModel {

  constructor(formSettings) {
    // custom form properties
    this.locationTypes = formSettings.locationTypes;
    this.selectedMarker = formSettings.selectedMarker;
    this.requireCityLimits = formSettings.requireCityLimits;
    this.displayCityLimits = formSettings.displayCityLimits;


    this.locationMap = [];
    this.mapLayers = [];
  }

  addTodo(text) {
    // this.locationMap.push({ text, completed: false });
  }

  toggleTodo(index) {
    // this.locationMap[index].completed = !this.locationMap[index].completed;
  }
}
