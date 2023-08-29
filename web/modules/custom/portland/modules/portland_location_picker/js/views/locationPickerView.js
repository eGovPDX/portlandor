(function ($) {
  class LocationPickerView {
    constructor(controller) {
      this.controller = controller;
      this.map = this.initMap();
      this.loadMapData();
      // this.todoList = document.querySelector('#todo-list');
      // this.addBtn = document.querySelector('#add-todo');
      // this.addInput = document.querySelector('#todo-text');

      // this.addBtn.addEventListener('click', () => this.controller.addTodoItem(this.addInput.value));
    }

    initMap() {

      // look for map container
      // TODO: add support for more than one map;
      var container = $('#location_map_container');
      if (container.length < 1) {
        this.controller.logError("Map container not found.");
        return false;
      }

      var map = new L.Map(MAP_CONTAINER_ID, {
        center: new L.LatLng(DEFAULT_LATITUDE, DEFAULT_LONGITUDE),
        zoomControl: false,
        zoom: DEFAULT_ZOOM,
        gestureHandling: true
      });
      map.addControl(new L.control.zoom({ position: ZOOM_POSITION }));
      // map.addControl(this.generateAerialControl());
      // map.addControl(this.generateLocateControl());
      //map.on('locationerror', handleLocationError);
      //map.on('locationfound', handleLocateMeFound);

      return map;
    }

    loadMapData() {

    }

    generateLocateControl() {
      return L.Control.extend({
        options: {
          position: "bottomright"
        },
        onAdd: function (map) {
          locateControlContaier = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
          locateControlContaier.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_locate.png)";
          locateControlContaier.title = 'Locate Me';
          //locateControlContaier.onclick = handleLocateButtonClick;
          return locateControlContaier;
        }
      });
    }

    generateAerialControl() {
      return L.Control.extend({
        options: {
          position: "bottomright"
        },
        onAdd: function (map) {
          aerialControlContainer = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
          aerialControlContainer.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png)";
          aerialControlContainer.title = 'Aerial view';
          //aerialControlContainer.onclick = handleAerialButtonClick;
          return aerialControlContainer;
        }
      });
    }
  }

  // Export the view class
  window.LocationPickerView = LocationPickerView;
})(jQuery);
