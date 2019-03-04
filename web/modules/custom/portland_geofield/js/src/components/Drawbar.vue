<template>
  <div class="topbar">
    <DrawButton type="point"/>
    <DrawButton type="multipoint"/>
    <DrawButton type="polyline"/>
    <DrawButton type="polygon"/>
    <DrawButton type="rectangle"/>
    <DrawButton type="circle"/>
    <button
      name="reset"
      class="action-button esri-icon-trash reset"
      type="button"
      title="Clear graphics"
      @click="clearGraphics"
    ></button>
  </div>
</template>

<script>
import { mapActions, mapState } from "vuex";
import * as esriLoader from "esri-loader";

import DrawButton from "@/components/DrawButton";
import symbols from "../utilities/symbols";

export default {
  name: "Drawbar",
  components: {
    DrawButton
  },
  created: function() {
    esriLoader
      .loadModules([
        "esri/layers/GraphicsLayer",
        "esri/widgets/Sketch/SketchViewModel"
      ])
      .then(([GraphicsLayer, SketchViewModel]) => {
        // create a new sketch view model
        const sketchViewModel = new SketchViewModel({
          view: this.mapView,
          layer: new GraphicsLayer(),
          pointSymbol: symbols.point,
          polylineSymbol: symbols.polyline,
          polygonSymbol: symbols.polygon
        });

        sketchViewModel.on("create", event => {
          if (event.state === "complete") {
            this.clearMessages();
            const geometry = event.graphic.geometry;
            this.addGeometry(geometry);
            let { x, y } = geometry.extent ? geometry.extent.center : geometry;
            this.setAddressField({ x, y });
            esriLoader
              .loadModules(["esri/geometry/support/webMercatorUtils"])
              .then(([webMercatorUtils]) => {
                this.setValueField(
                  webMercatorUtils.webMercatorToGeographic(geometry)
                );
              });
          }
        });

        sketchViewModel.on("update", event => {
          if (event.state === "complete") {
            this.clearMessages();
            const geometry = event.graphic.geometry;
            this.addGeometry(geometry);
            let { x, y } = geometry.extent ? geometry.extent.center : geometry;
            this.setAddressField({ x, y });
            esriLoader
              .loadModules(["esri/geometry/support/webMercatorUtils"])
              .then(([webMercatorUtils]) => {
                this.setValueField(
                  webMercatorUtils.webMercatorToGeographic(geometry)
                );
              });
          }
        });

        this.setSketchViewModel(sketchViewModel);
      });
  },
  computed: {
    ...mapState({
      sketchViewModel: state => state.drawbar.sketchViewModel,
      mapView: state => state.map.mapView
    })
  },
  methods: {
    ...mapActions([
      "clearGraphics",
      "setSketchViewModel",
      "addGeometry",
      "setAddressField",
      "setValueField",
      "clearMessages"
    ])
  }
};
</script>

<style>
.portland-geofield-map-app .topbar {
  background: #fff;
  position: absolute;
  top: 1rem;
  right: 1rem;
  padding: 0.5rem;
}
</style>
