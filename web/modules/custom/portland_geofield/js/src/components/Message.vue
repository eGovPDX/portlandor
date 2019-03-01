<template>
  <div :class="[alertClass, typeClass]" role="alert">
    {{ message }}
  </div>
</template>

<script>
export default {
  name: 'Message',
  props: [
    'message',
    'type'
  ],
  data: function() {
    return {
      alertClass: 'alert',
    }
  },
  computed: {
    typeClass: function() {
      return `alert-${this.type}`;
    }
  }
}
</script>

<style>

</style>
