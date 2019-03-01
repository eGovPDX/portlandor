<template>
  <div id="messages">
    <Message v-for="message in messages" :key="message.id" :message="message.text" :type="message.type"></Message>
  </div>
</template>

<script>
import { mapState } from 'vuex';

import Message from '@/components/Message';

export default {
  name: 'Messages',
  components: {
    Message
  },
  computed: {
    ...mapState([
      'messages',
    ])
  },
}
</script>

<style>

</style>
