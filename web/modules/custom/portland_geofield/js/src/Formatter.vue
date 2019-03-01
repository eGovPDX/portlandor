<template>
  <div :id="mapid">
    <Map/>
  </div>
</template>

<script>
import { mapGetters } from "vuex";

import Map from "./components/Map";

export default {
  name: "Formatter",
  components: {
    Map
  },
  computed: {
    ...mapGetters(["mapid"])
  },
  methods: {}
};
</script>

<style>
#app {
  font-family: "Avenir", Helvetica, Arial, sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
  text-align: center;
  color: #2c3e50;
}
</style>
