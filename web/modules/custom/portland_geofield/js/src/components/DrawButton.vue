<template>
  <button
    :name="type"
    :class="[{ 'action-button': true, active: active }, icon, type]"
    type="button"
    :title="title"
    @click="draw(type, $event)"
  ></button>
</template>

<script>
import { mapActions, mapState } from "vuex";

const icons = {
  point: "esri-icon-blank-map-pin",
  multipoint: "esri-icon-handle-horizontal",
  polyline: "esri-icon-polyline",
  polygon: "esri-icon-polygon",
  rectangle: "esri-icon-checkbox-unchecked",
  circle: "esri-icon-radio-unchecked"
};

export default {
  name: "DrawButton",
  props: ["type"],
  computed: {
    icon: function() {
      return icons[this.type];
    },
    title: function() {
      return `Draw ${this.type}`;
    },
    active: function() {
      return this.type === this.activeButton;
    },
    ...mapState({
      activeButton: state => state.drawbar.activeButton
    })
  },
  methods: {
    ...mapActions(["draw"])
  }
};
</script>

<style>
.portland-geofield-map-app .topbar .action-button {
  background-color: transparent;
  border: 1px solid #d3d3d3;
  color: #6e6e6e;
  padding: 0.5rem;
  text-align: center;
  box-shadow: 0 0 1px rgba(0, 0, 0, 0.3);
}
.portland-geofield-map-app .topbar .action-button:hover,
.portland-geofield-map-app .topbar .action-button:focus {
  background: #154778;
  color: #ffffff;
}
.portland-geofield-map-app .topbar .action-button.active {
  background: #154778;
  color: #ffffff;
}
</style>
