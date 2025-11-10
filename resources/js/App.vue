<template>
  <div id="app">
    <Menu v-if="!isPrintPage" />
    <router-view />
  </div>
</template>

<script>
import Menu from './components/common/Menu.vue'
import { computed } from 'vue'
import { useRoute } from 'vue-router'

export default {
  name: 'App',
  components: {
    Menu,
  },
  setup() {
    const route = useRoute()
    const isPrintPage = computed(() => route.path === '/print')
    
    return {
      isPrintPage,
    }
  },
}
</script>

<style>
body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 20px;
}
</style>

