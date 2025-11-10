import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import App from './App.vue'
import ClockInOut from './views/ClockInOut.vue'
import WeekView from './views/WeekView.vue'
import Users from './views/Users.vue'
import Groups from './views/Groups.vue'
import PrintView from './views/PrintView.vue'
import './bootstrap'

const routes = [
  { path: '/', component: ClockInOut },
  { path: '/week/:userId?', component: WeekView },
  { path: '/users', component: Users },
  { path: '/groups', component: Groups },
  { path: '/print', component: PrintView },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

const app = createApp(App)
app.use(router)
app.mount('#app')
