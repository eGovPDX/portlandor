// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue'
import Widget from './Widget'
import store from './store';

Vue.config.productionTip = false

/* eslint-disable no-new */
new Vue({
  el: '#app',
  store,
  components: { Widget },
  template: '<Widget/>'
});
store.state.widget = true;

// var settings = drupalSettings['portland_geofield_map'][mapid];
// store.state.settings = settings;
store.state.settings.mapid = 'mapid';

