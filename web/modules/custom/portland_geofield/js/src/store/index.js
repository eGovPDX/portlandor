import Vue from "vue";
import Vuex from "vuex";

import map from "./modules/map";
import drawbar from "./modules/drawbar";
import search from "./modules/search";
import field from "./modules/field";

Vue.use(Vuex);

// initial state
// shape: [{ id, quantity }]
const state = {
  messages: [],
  widget: false,
  settings: {
    wktid: "",
    mapid: "",
    width: "100%",
    height: "50vh",
    center: {
      lon: -13656529.895,
      lat: 5703070.921
    },
    zoom: {
      start: 12,
      focus: 16
    },
    value: {}
  }
};

// getters
const getters = {
  mapid: state => state.settings.mapid + "-app"
};

// actions
const actions = {
  initialize() {},
  setMessage({ commit }, message) {
    commit("setMessage", { message });
  },
  clearMessages({ commit }) {
    commit("clearMessages");
  }
};

// mutations
const mutations = {
  setMessage(state, { message }) {
    state.messages = [message];
  },
  clearMessages(state) {
    state.messages = [];
  }
};

export default new Vuex.Store({
  modules: {
    map,
    drawbar,
    search,
    field
  },
  state,
  getters,
  actions,
  mutations
});
