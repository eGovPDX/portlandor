import { PortlandMaps } from "../../api/portlandmaps";

const portlandmaps = new PortlandMaps("4F82E619ACB509A261A1D3E9C146E190");

const state = {};

// getters
const getters = {};

// actions
const actions = {
  suggest({ commit, dispatch }, address) {
    commit("clearMessages");
    portlandmaps.suggestAddress(
      address,
      {},
      value => {
        dispatch("center", {
          location: value.location,
          spatialReference: value.spatialReference
        });
      },
      reason => {
        commit("setMessage", {
          message: {
            id: "PORTLANDMAPS_REQUEST_ERROR",
            text: reason,
            type: "danger"
          }
        });
      }
    );
  }
};

// mutations
const mutations = {};

export default {
  state,
  getters,
  actions,
  mutations
};
