<template>
  <div class="menu">
    <div class="table_component">
      <br>
      SmallTime - A minimalist time-clock application
      <br><br>
      <table>
        <tr>
          <td :class="{ active: $route.path === '/' }">
            <button @click="goToClockInOut" class="menu-button">Clock In/Out</button>
          </td>
          <td :class="{ active: $route.path.startsWith('/week') }">
            <select id="week-user-select" name="week-user-select" v-model="selectedUserId" @change="goToWeek" class="menu-select" title="Select an employee to view time for a week">
              <option value="">Select user...</option>
              <option v-for="user in users" :key="user.id" :value="user.id">
                {{ user.name }}
              </option>
            </select>
          </td>
          <td :class="{ active: $route.path === '/users' }">
            <button @click="goToUsers" class="menu-button">Manage Users</button>
          </td>
          <td :class="{ active: $route.path === '/groups' }">
            <button @click="goToGroups" class="menu-button">Manage Companies</button>
          </td>
        </tr>
      </table>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../../api/index.js'

export default {
  name: 'Menu',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const users = ref([])
    const selectedUserId = ref('')

    const goToWeek = () => {
      if (selectedUserId.value) {
        // Preserve cursorDateTime from current route if available
        const cursorDateTime = route.query.cursorDateTime
        const query = cursorDateTime ? { cursorDateTime } : {}
        router.push({
          path: `/week/${selectedUserId.value}`,
          query: query
        })
      }
    }

    const goToClockInOut = () => {
      router.push('/')
    }

    const goToUsers = () => {
      router.push('/users')
    }

    const goToGroups = () => {
      router.push('/groups')
    }

    // Load users function
    const loadUsers = async () => {
      try {
        const response = await api.users.getAll()
        users.value = response.data.data || []
      } catch (error) {
        console.error('Error loading users:', error)
      }
    }

    // Watch route changes and reset dropdown when not on week tab
    watch(() => route.path, async (newPath) => {
      if (newPath.startsWith('/week')) {
        // Reload users list when navigating to week tab
        await loadUsers()
        if (route.params.userId) {
          selectedUserId.value = parseInt(route.params.userId)
        }
      } else {
        selectedUserId.value = ''
      }
    }, { immediate: true })

    onMounted(async () => {
      await loadUsers()
      
      // Set selected user if on week page
      if (route.path.startsWith('/week') && route.params.userId) {
        selectedUserId.value = parseInt(route.params.userId)
      } else {
        selectedUserId.value = ''
      }
    })

    return {
      users,
      selectedUserId,
      goToWeek,
      goToClockInOut,
      goToUsers,
      goToGroups,
    }
  },
}
</script>

<style scoped>
.menu {
  padding: 0 20px;
}
table {
  border-collapse: collapse;
  width: 100%;
  max-width: 800px;
}
th, td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
}
td.active {
  background-color: lightgrey !important;
}
td.active .menu-select {
  background-color: white !important;
}
th {
  background-color: #f2f2f2;
  font-weight: bold;
}
th, td {
  width: 25%;
}
.menu-button {
  width: 100%;
  padding: 8px;
  background-color: #007bff;
  color: white;
  border: 1px solid #007bff;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
  text-align: center;
}
.menu-button:hover {
  background-color: #0056b3;
}
.menu-select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  font-size: 14px;
}
</style>

