import Vue from "vue";
import Formatter from "./Formatter";
import store from "./store";
// eslint-disable-next-line
import config from "./config";

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
              el: `#${mapid}`,
              store: store,
              components: {
                Formatter
              },
              template: "<Formatter/>"
            });

            store.state.settings = settings;
          });
      }
    }
  };
})(jQuery, Drupal, drupalSettings);
