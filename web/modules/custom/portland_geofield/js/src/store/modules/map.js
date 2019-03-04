import { loadModules } from "esri-loader";

import symbols from "../../utilities/symbols";
import converter from "../../utilities/converter";

const state = {
  mapView: {},
  values: [],
  center: {},
  zoom: {
    start: 12,
    focus: 16
  }
};

// getters
const getters = {};

// actions
const actions = {
  setMapView({ commit }, mapView) {
    commit("setMapView", { mapView: mapView });
  },
  addWkt({ dispatch }, wkt) {
    dispatch("addGeometry", converter.toArcgis(wkt));
  },
  addGeometry({ commit, state }, geometry) {
    loadModules(["esri/Graphic"]).then(([Graphic]) => {
      const graphic = new Graphic({
        geometry: geometry,
        symbol: symbols[geometry.type]
      });

      state.mapView.when(() => {
        commit("addGraphic", { graphic: graphic });
      });
    });

    commit("clearActiveButton");
  },
  clearGraphics({ commit }) {
    commit("clearGraphics");
  },
  center(
    { commit },
    {
      location: { x, y },
      spatialReference
    }
  ) {
    loadModules(["esri/geometry/Point"]).then(([Point]) => {
      const point = new Point({
        x,
        y,
        spatialReference
      });

      commit("center", { point: point });
      commit("focus");
    });
  }
};

// mutations
const mutations = {
  setMapView(state, { mapView }) {
    state.mapView = mapView;
  },
  addGraphic(state, { graphic }) {
    state.mapView.graphics.add(graphic);
    state.mapView.goTo({ target: graphic, zoom: state.zoom.focus });
  },
  clearGraphics(state) {
    state.mapView.graphics.removeAll();
  },
  center(state, { point }) {
    state.mapView.goTo({ center: point, zoom: state.zoom.focus });
  },
  focus(state) {
    state.mapView.zoom = state.zoom.focus;
  }
};

export default {
  state,
  getters,
  actions,
  mutations
};
