const state = {
  sketchViewModel: {},
  activeButton: ""
};

// getters
const getters = {};

// actions
const actions = {
  setSketchViewModel({ commit }, sketchViewModel) {
    commit("setSketchViewModel", { sketchViewModel });
  },
  draw({ commit }, type) {
    commit("setActiveButton", { type });
    commit("clearGraphics");
    commit("setMessage", {
      message: {
        id: "MAP_HELP",
        text:
          type === "point"
            ? "Drawing started... Click to place a point."
            : `Drawing started... Double-click or press "c" to end drawing.`,
        type: "info"
      }
    });
    commit("startDraw", { type });
  }
};

// mutations
const mutations = {
  setSketchViewModel(state, { sketchViewModel }) {
    state.sketchViewModel = sketchViewModel;
  },
  startDraw(state, { type }) {
    state.sketchViewModel.create(type);
  },
  setActiveButton(state, { type }) {
    state.activeButton = type;
  },
  clearActiveButton(state) {
    state.activeButton = "";
  }
};

export default {
  state,
  getters,
  actions,
  mutations
};
