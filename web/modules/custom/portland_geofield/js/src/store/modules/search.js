import { PortlandMaps } from "../../api/portlandmaps";

const portlandmaps = new PortlandMaps('4F82E619ACB509A261A1D3E9C146E190');

const returnVal = {
  "status": "success",
  "spatialReference": {
    "wkid": 102100,
    "latestWkid": 3857
  },
  "candidates": [{
    "location": {
      "x": -13656452.873,
      "y": 5703102.290
    },
    "attributes": {
      "city": "PORTLAND",
      "jurisdiction": "PORTLAND",
      "state": "OREGON",
      "id": 54286,
      "type": "intersection",
      "county": "MULTNOMAH"
    },
    "address": "SW 4TH AVE AND SW MAIN ST",
    "extent": {
      "ymin": 5703102.04,
      "ymax": 5703102.54,
      "xmin": -1.3656453123E7,
      "xmax": -1.3656452623E7
    }
  }]
}

const state = {

};

// getters
const getters = {

};

// actions
const actions = {
  suggest({ commit, dispatch }, address) {
    commit('clearMessages');
    portlandmaps.suggestAddress(
      address,
      {},
      (value) => {
        dispatch('center', {
          location: value.location,
          spatialReference: value.spatialReference
        })
      },
      (reason) => {
        commit('setMessage', {
          message: {
            id: 'PORTLANDMAPS_REQUEST_ERROR',
            text: reason,
            type: 'danger',
          }
        });
      }
    );
  }
};

// mutations
const mutations = {

};

export default {
  state,
  getters,
  actions,
  mutations,
};

