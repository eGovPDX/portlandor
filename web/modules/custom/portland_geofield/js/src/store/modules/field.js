import { GeocodeServer } from "../../api/geocodeserver";
import { AddressWrapper } from "../../utilities/addresswrapper";
import { ValueWrapper } from "../../utilities/valuewrapper";

const state = {
  addressField: "field_facility_address",
  valueField: "edit-field-geometry-0-value-wkt"
};

const getters = {};

const actions = {
  setAddressField({ commit, dispatch }, { x, y }) {
    const geocoder = new GeocodeServer();
    geocoder.getAddress(x, y).then(response => {
      if (!response || response.data.error) {
        dispatch("setMessage", {
          id: "GEOCODESERVER_GEOCODE_ERROR",
          text: "Unable to geocode an address for the geometry.",
          type: "danger"
        });
      } else if (response.data.address) {
        commit("setAddressField", { address: response.data.address });
      }
    });
  },
  setValueField({ commit }, geometry) {
    commit("setValueField", { geometry });
  }
};

const mutations = {
  setAddressField(state, { address }) {
    const addressWrapper = new AddressWrapper(state.addressField);
    addressWrapper.set(address);
  },
  clearValueField(state) {
    const valueWrapper = new ValueWrapper(state.valueField);
    valueWrapper.clear();
  },
  setValueField(state, { geometry }) {
    const valueWrapper = new ValueWrapper(state.valueField);
    valueWrapper.set(geometry);
  }
};

export default {
  state,
  getters,
  actions,
  mutations
};
