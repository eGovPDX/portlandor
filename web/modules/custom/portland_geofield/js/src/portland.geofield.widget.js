import Vue from "vue";
import Widget from "./Widget";
import store from "./store";

(function($, Drupal) {
  Drupal.behaviors.esri = {
    attach: function(context, drupalSettings) {
      if (drupalSettings["portland_geofield_map"]) {
        $(context)
          .find(".portland-geofield-map")
          .once("portland_processed")
          .each(function(index, element) {
            var mapid = $(element).attr("id");
            var settings = drupalSettings["portland_geofield_map"][mapid];
            /* eslint-disable no-new */
            new Vue({
              store: store,
              render: h => h(Widget)
            }).$mount(`#${mapid}`);

            store.state.settings = settings;
            store.state.widget = true;
            store.state.field.addressField = settings.addressField;
            store.state.field.valueField = settings.wktid;
          });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
