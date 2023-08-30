(function ($, Drupal, drupalSettings, L) {
  /**
   * Represents a Location Picker View.
   * This model generates and manipulates the user interface for the location picker.
   * 
   */
  class LocationPickerView {

    constructor(controller) {
      this.controller = controller;
      this.baseLayer = L.tileLayer(BASEMAP_DEFAULT_URL, { attribution: BASEMAP_DEFAULT_ATTRIBUTION });
      this.aerialLayer = L.tileLayer(BASEMAP_AERIAL_URL, { attribution: BASEMAP_AERIAL_ATTRIBUTION });
      this.map = this.initMap();
      this.loadMapData();
      this.aerialControlContainer;
      this.currentView = "base";
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
      map.addLayer(this.baseLayer);
      map.addControl(new L.control.zoom({ position: ZOOM_POSITION }));

      this.aerialLayer = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
        attribution: 'Tiles &copy; Esri'
      });//.addTo(map);

      var aerialControl = L.control({ position: 'bottomright' });
      const self = this;

      aerialControl.onAdd = function (map) {

        this.aerialControlContainer = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
        this.aerialControlContainer.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png)";
        this.aerialControlContainer.title = 'Aerial view';
        L.DomEvent.disableClickPropagation(this.aerialControlContainer);


        this.aerialControlContainer.onclick = function(e) {
          if (this.currentView == "aerial") {
            map.removeLayer(self.aerialLayer);
            self.currentView = "base";
            e.currentTarget.style.background = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_aerial.png")';
          } else {
            map.addLayer(self.aerialLayer);
            self.currentView = "aerial";
            e.currentTarget.style.background = 'url("/modules/custom/portland/modules/portland_location_picker/images/map_base.png")';
          }
        };
        return this.aerialControlContainer;
  
        var div = L.DomUtil.create('div', 'aerial-control');
        div.innerHTML = '<label><input type="checkbox"> Aerial View</label>';
        L.DomEvent.disableClickPropagation(div);

        var input = div.querySelector('input');
        input.addEventListener('change', function () {
          if (input.checked) {
            map.addLayer(aerialLayer);
          } else {
            map.removeLayer(aerialLayer);
          }
        });

        return div;
      };

      aerialControl.addTo(map);

      // map.addControl(L.Control.extend({
      //   options: {
      //     position: "bottomright"
      //   },
      //   onAdd: function (map) {
      //     locateControlContaier = L.DomUtil.create('div', 'leaflet-bar locate-control leaflet-control leaflet-control-custom');
      //     locateControlContaier.style.backgroundImage = "url(/modules/custom/portland/modules/portland_location_picker/images/map_locate.png)";
      //     locateControlContaier.title = 'Locate Me';
      //     //locateControlContaier.onclick = handleLocateButtonClick;
      //     return locateControlContaier;
      //   }
      // }));
      // map.addControl(this.generateLocateControl());
      //map.on('locationerror', handleLocationError);
      //map.on('locationfound', handleLocateMeFound);

      return map;
    }

    loadMapData() {

    }

    generateLocateControl() {
      return new L.Control.extend({
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

    handleAerialButtonClick(e) {
      cancelEventBubble(e);
      locationErrorShown = false;
      //toggleAerialView();
      alert('toggle view');
    }


  }

  // Export the view class
  window.LocationPickerView = LocationPickerView;
})(jQuery, Drupal, drupalSettings, L);
