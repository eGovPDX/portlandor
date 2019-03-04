<template>
  <div :id="settings.mapid" class="portland-geofield-map-app" :style="style">
    <slot></slot>
  </div>
</template>

<script>
import { mapState, mapActions } from "vuex";
import { loadModules } from "esri-loader";

export default {
  name: "Map",
  components: {},
  created: function() {
    loadModules(["esri/Map", "esri/views/MapView", "esri/widgets/Locate"]).then(
      ([EsriMap, MapView, Locate]) => {
        const map = new EsriMap({
          basemap: this.settings.basemap || "streets-vector"
        });

        const mapView = new MapView({
          container: this.settings.mapid,
          map: map,
          zoom: this.settings.zoom.start || 10,
          center: [this.settings.center.lon, this.settings.center.lat]
        });

        this.setMapView(mapView);

        if (this.widget) {
          var locateBtn = new Locate({
            view: mapView
          });
          // Add the locate widget to the top left corner of the view
          mapView.ui.add(locateBtn, {
            position: "top-left"
          });
        }

        if (this.settings.value) {
          //add a graphic for the values
          for (const delta in this.settings.value) {
            if (this.settings.value.hasOwnProperty(delta)) {
              const element = this.settings.value[delta];
              this.addWkt(element.wkt);
            }
          }
          //disable scrolling
        }
      }
    );
  },
  computed: {
    style: function() {
      return {
        width: this.settings.width,
        height: this.settings.height
      };
    },
    ...mapState({
      widget: "widget",
      settings: "settings",
      extent: "extent",
      value: "value",
      mapView: state => state.map.mapView
    })
  },
  methods: {
    ...mapActions(["addWkt", "setMapView"])
  }
};
</script>

<style>
.portland-geofield-map-app {
  position: relative;
}
@media print {
  .esri-ui {
    display: none;
  }

  .esri-view-user-storage {
    display: none;
  }
}
</style>
