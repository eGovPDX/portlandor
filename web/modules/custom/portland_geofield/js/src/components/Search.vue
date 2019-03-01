<template>
  <div id="addressSearchBar">
    <div class="input-group">
      <input type="text"
        class="form-control"
        id="addressSearch"
        placeholder="Search for a location..."
        v-model="searchValue"
        @keypress.enter.prevent.stop="update"
        @keyup.esc="cancelEdit" />
      <span class="input-group-btn">
        <button class="btn btn-primary" type="button" @click="update">Search</button>
      </span>
    </div>
  </div>
</template>

<script>
import { mapState, mapActions } from 'vuex';
var _ = require('lodash');

export default {
  name: 'Search',
  data: function() {
    return {
      searchValue: '',
    }
  },
  computed: {
    ...mapState([
      'error',
    ])
  },
  methods: {
    update:
      _.debounce(function(e) {
        if(this.searchValue) {
          this.suggest(this.searchValue);
        }
      }, 1000)
    ,
    cancelEdit (e) {
      this.searchValue = '';
    },
    ...mapActions([
      'suggest',
    ]),
  }
}
</script>

<style>
#addressSearchBar {
  position: absolute;
  width: 50%;
  bottom: 2rem;
  left: 50%;
  margin-right: -50%;
  transform: translate(-50%, -50%);
}
</style>
