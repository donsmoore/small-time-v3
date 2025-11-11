<template>
  <div class="users">
    <div class="actions">
      <button id="add-user-btn" name="add-user-btn" @click="openAddForm">Add User</button>
      <div class="pagination-arrows">
        <button class="pagination-arrow" @click="goToFirstPage" :disabled="currentPage === 1">
          <span>«</span>
        </button>
        <button class="pagination-arrow" @click="goToPrevPage" :disabled="currentPage === 1">
          <span>‹</span>
        </button>
        <div class="page-info-wrapper">
          <span class="page-info">Page {{ currentPage }} of {{ totalPages }}</span>
        </div>
        <button class="pagination-arrow" @click="goToNextPage" :disabled="currentPage === totalPages">
          <span>›</span>
        </button>
        <button class="pagination-arrow" @click="goToLastPage" :disabled="currentPage === totalPages">
          <span>»</span>
        </button>
      </div>
      <div class="company-filter">
        <label for="company-filter-select">Company:</label>
        <select id="company-filter-select" v-model="selectedCompany" @change="onCompanyFilterChange">
          <option value="all">All</option>
          <option v-for="group in sortedGroups" :key="group.id" :value="group.id">
            {{ group.groupName }}
          </option>
        </select>
        <button id="company-filter-btn" name="company-filter-btn" class="company-filter-btn" title="View all companies" @click="resetCompanyFilter"><></button>
      </div>
    </div>

    <div v-if="loading">Loading...</div>
    <div v-else-if="error" class="error-message">{{ error }}</div>
    <table v-else class="users-table">
      <thead>
        <tr>
          <th @click="sortBy('id')" class="sortable id-column" :class="{ 'sorted-column': sortColumn === 'id' }">
            ID
            <span class="sort-indicator" :class="{ 'sort-indicator-visible': sortColumn === 'id' }">
              {{ sortColumn === 'id' && sortDirection === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
          <th @click="sortBy('name')" class="sortable name-column" :class="{ 'sorted-column': sortColumn === 'name' }">
            Name
            <span class="sort-indicator" :class="{ 'sort-indicator-visible': sortColumn === 'name' }">
              {{ sortColumn === 'name' && sortDirection === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
          <th @click="sortBy('userCode')" class="sortable code-column" :class="{ 'sorted-column': sortColumn === 'userCode' }">
            Code
            <span class="sort-indicator" :class="{ 'sort-indicator-visible': sortColumn === 'userCode' }">
              {{ sortColumn === 'userCode' && sortDirection === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
          <th @click="sortBy('group')" class="sortable company-column" :class="{ 'sorted-column': sortColumn === 'group' }">
            Company
            <span class="sort-indicator" :class="{ 'sort-indicator-visible': sortColumn === 'group' }">
              {{ sortColumn === 'group' && sortDirection === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
          <th class="actions-column">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="filteredUsers.length === 0">
          <td colspan="5" class="no-records">No users found</td>
        </tr>
        <tr v-for="user in paginatedUsers" :key="user.id">
          <td class="id-column" style="text-align: center;">{{ user.id }}</td>
          <td class="name-column">
            <span class="name-text">{{ user.name }}</span>
            <button :id="`view-user-week-btn-${user.id}`" :name="`view-user-week-btn-${user.id}`" @click="viewUserWeek(user)" class="name-arrow-btn" title="View Time">▶</button>
          </td>
          <td class="code-column">{{ user.userCode }}</td>
          <td class="company-column">{{ getGroupName(user.groupId) }}</td>
          <td class="actions-column"><div class="action-cell-content">
            <button :id="`edit-user-btn-${user.id}`" :name="`edit-user-btn-${user.id}`" @click="editUser(user)" class="x-button edit-action-btn">Edit</button>
            <button :id="`delete-user-btn-${user.id}`" :name="`delete-user-btn-${user.id}`" @click="deleteUser(user.id)" class="x-button delete-action-btn">Delete</button>
          </div></td>
        </tr>
      </tbody>
    </table>

    <!-- Add/Edit Modal -->
    <div v-if="showAddForm || editingUser" class="modal-overlay" @mousedown="handleOverlayMouseDown($event, 'addEdit')">
      <div ref="addEditModalContent" class="modal-content" :style="addEditModalStyle" @click.stop>
        <div class="modal-title-box" @mousedown="startDrag($event, 'addEdit')" style="cursor: move;">
          <h3>{{ editingUser ? 'Edit User' : 'Add User' }}</h3>
        </div>
        <form @submit.prevent="saveUser">
          <div class="form-group">
            <label for="user-name">Name:</label>
            <input id="user-name" name="user-name" ref="nameInput" v-model="formData.name" required />
          </div>
          <div class="form-group">
            <div class="label-with-error">
              <label for="user-code">User Code:</label>
              <span v-if="userCodeError" class="user-code-error">{{ userCodeError }}</span>
            </div>
            <input id="user-code" name="user-code" v-model="formData.userCode" @input="clearUserCodeError" required />
          </div>
          <div class="form-group">
            <label for="user-group">Company:</label>
            <select id="user-group" name="user-group" v-model.number="formData.groupId">
              <option v-for="group in groups" :key="group.id" :value="group.id">
                {{ group.groupName }}
              </option>
            </select>
          </div>
          <div class="form-actions">
            <button id="save-user-btn" name="save-user-btn" type="submit" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
            <button id="cancel-user-btn" name="cancel-user-btn" type="button" @click="closeModal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deletingUser" class="modal-overlay" @mousedown="handleOverlayMouseDown($event, 'delete')">
      <div ref="deleteModalContent" class="modal-content" :style="deleteModalStyle" @click.stop>
        <div class="modal-title-box" @mousedown="startDrag($event, 'delete')" style="cursor: move;">
          <h3>Delete User</h3>
        </div>
        <div class="delete-confirmation-content">
          <p>Are you sure you want to delete <strong>{{ deletingUser.name }}</strong>?</p>
          <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="form-actions">
          <button id="confirm-delete-user-btn" name="confirm-delete-user-btn" type="button" @click="confirmDelete" class="delete-confirm-btn">Delete</button>
          <button id="cancel-delete-user-btn" name="cancel-delete-user-btn" type="button" @click="closeDeleteModal">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed, watch, onUnmounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '../api/index.js'

export default {
  name: 'Users',
  setup() {
    const router = useRouter()
    const route = useRoute()
    const users = ref([])
    const loading = ref(false)
    const error = ref('')
    const showAddForm = ref(false)
    const editingUser = ref(null)
    const deletingUser = ref(null)
    const saving = ref(false)
    const formData = ref({
      name: '',
      userCode: '',
      groupId: 0,
    })
    const groups = ref([])
    const sortColumn = ref(localStorage.getItem('usersSortColumn') || 'id')
    const sortDirection = ref(localStorage.getItem('usersSortDirection') || 'asc')
    const nameInput = ref(null)
    const currentPage = ref(1)
    const itemsPerPage = ref(5)
    const selectedCompany = ref(route.query.companyId ? route.query.companyId : 'all')
    const userCodeError = ref('')
    
    // Drag functionality
    const addEditModalContent = ref(null)
    const deleteModalContent = ref(null)
    const dragging = ref({ active: false, type: null, startX: 0, startY: 0, offsetX: 0, offsetY: 0 })
    const addEditModalStyle = ref({ transform: 'translate(0, 0)' })
    const deleteModalStyle = ref({ transform: 'translate(0, 0)' })
    
    const startDrag = (event, modalType) => {
      event.preventDefault()
      dragging.value = {
        active: true,
        type: modalType,
        startX: event.clientX,
        startY: event.clientY,
        offsetX: 0,
        offsetY: 0
      }
      document.addEventListener('mousemove', handleDrag)
      document.addEventListener('mouseup', stopDrag)
    }
    
    const handleDrag = (event) => {
      if (!dragging.value.active) return
      
      const offsetX = event.clientX - dragging.value.startX
      const offsetY = event.clientY - dragging.value.startY
      
      dragging.value.offsetX = offsetX
      dragging.value.offsetY = offsetY
      
      const style = { transform: `translate(${offsetX}px, ${offsetY}px)` }
      
      if (dragging.value.type === 'addEdit') {
        addEditModalStyle.value = style
      } else if (dragging.value.type === 'delete') {
        deleteModalStyle.value = style
      }
    }
    
    const stopDrag = () => {
      dragging.value.active = false
      document.removeEventListener('mousemove', handleDrag)
      document.removeEventListener('mouseup', stopDrag)
    }
    
    const handleOverlayMouseDown = (event, modalType) => {
      // Only close if clicking directly on the overlay (not on modal content or its children)
      if (event.target === event.currentTarget) {
        // Check if user is selecting text
        const selection = window.getSelection()
        const hasSelection = selection && selection.toString().length > 0
        
        // Check if mouse moved (drag vs click)
        const startX = event.clientX
        const startY = event.clientY
        
        const handleMouseUp = (e) => {
          const endX = e.clientX
          const endY = e.clientY
          const moved = Math.abs(endX - startX) > 5 || Math.abs(endY - startY) > 5
          
          // Check again for text selection on mouseup
          const finalSelection = window.getSelection()
          const hasFinalSelection = finalSelection && finalSelection.toString().length > 0
          
          // Only close if it was a click (not a drag) and no text is selected
          if (!moved && !hasSelection && !hasFinalSelection) {
            if (modalType === 'addEdit') {
              closeModal()
            } else if (modalType === 'delete') {
              closeDeleteModal()
            }
          }
          
          document.removeEventListener('mouseup', handleMouseUp)
        }
        
        document.addEventListener('mouseup', handleMouseUp)
      }
    }
    
    const resetModalPositions = () => {
      addEditModalStyle.value = { transform: 'translate(0, 0)' }
      deleteModalStyle.value = { transform: 'translate(0, 0)' }
    }
    
    onUnmounted(() => {
      document.removeEventListener('mousemove', handleDrag)
      document.removeEventListener('mouseup', stopDrag)
    })

    const loadGroups = async () => {
      try {
        const response = await api.groups.getAll()
        groups.value = response.data.data || []
      } catch (err) {
        console.error('Error loading groups:', err)
      }
    }

    const getGroupName = (groupId) => {
      if (!groupId) return 'N/A'
      const group = groups.value.find(g => g.id === groupId)
      return group ? group.groupName : `Group #${groupId}`
    }

    const loadUsers = async () => {
      loading.value = true
      error.value = ''
      try {
        const response = await api.users.getAll()
        users.value = response.data.data || []
      } catch (err) {
        error.value = err.response?.data?.error || err.message || 'Error loading users'
        console.error('Error loading users:', err)
      } finally {
        loading.value = false
      }
    }

    const openAddForm = () => {
      resetModalPositions()
      showAddForm.value = true
      userCodeError.value = ''
    }
    
    const editUser = (user) => {
      resetModalPositions()
      editingUser.value = user
      formData.value = {
        name: user.name,
        userCode: user.userCode,
        groupId: user.groupId || 0,
      }
      userCodeError.value = ''
    }
    
    const clearUserCodeError = () => {
      userCodeError.value = ''
    }

    const viewUserWeek = (user) => {
      router.push(`/week/${user.id}`)
    }

    const deleteUser = async (id) => {
      resetModalPositions()
      const user = users.value.find(u => u.id === id)
      deletingUser.value = user
    }

    const confirmDelete = async () => {
      if (!deletingUser.value) return
      
      const id = deletingUser.value.id
      try {
        await api.users.delete(id)
        await loadUsers()
        closeDeleteModal()
      } catch (err) {
        alert('Error deleting user: ' + (err.response?.data?.error || err.message))
      }
    }

    const closeDeleteModal = () => {
      deletingUser.value = null
    }

    const saveUser = async () => {
      // Clear any previous error
      userCodeError.value = ''
      
      // Validate userCode uniqueness before saving
      const userCodeToCheck = formData.value.userCode?.trim()
      if (!userCodeToCheck) {
        userCodeError.value = 'User Code is required.'
        return
      }

      // Check if userCode already exists
      const existingUser = users.value.find(user => 
        user.userCode === userCodeToCheck && 
        (!editingUser.value || user.id !== editingUser.value.id)
      )

      if (existingUser) {
        userCodeError.value = `Code "${userCodeToCheck}" is already in use.`
        return
      }

      saving.value = true
      try {
        if (editingUser.value) {
          await api.users.update(editingUser.value.id, formData.value)
          await loadUsers()
          closeModal()
        } else {
          const response = await api.users.create(formData.value)
          await loadUsers()
          closeModal()
          // Navigate to week tab with the newly created user
          const newUserId = response.data.data?.id
          if (newUserId) {
            router.push(`/week/${newUserId}`)
          }
        }
      } catch (err) {
        // Backend validation error (in case frontend check missed something)
        const errorMsg = err.response?.data?.error || err.response?.data?.message || err.message
        if (errorMsg.includes('userCode') || errorMsg.includes('unique')) {
          userCodeError.value = `Code "${userCodeToCheck}" is already in use.`
        } else {
          alert('Error saving user: ' + errorMsg)
        }
      } finally {
        saving.value = false
      }
    }

    const sortBy = (column) => {
      if (sortColumn.value === column) {
        // Toggle direction if clicking the same column
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc'
      } else {
        // Set new column and default to ascending
        sortColumn.value = column
        sortDirection.value = 'asc'
      }
    }

    const sortedUsers = computed(() => {
      if (!users.value || !Array.isArray(users.value)) {
        return []
      }
      
      if (!sortColumn.value) {
        return users.value
      }

      try {
        const sorted = [...users.value].sort((a, b) => {
          let aVal, bVal

          // Special handling for group sorting - sort by group name
          if (sortColumn.value === 'group') {
            aVal = getGroupName(a.groupId)
            bVal = getGroupName(b.groupId)
          } else {
            aVal = a[sortColumn.value]
            bVal = b[sortColumn.value]
          }

          // Handle null/undefined values
          if (aVal == null) aVal = ''
          if (bVal == null) bVal = ''

          // Special handling for ID - numeric sorting
          if (sortColumn.value === 'id') {
            aVal = Number(aVal) || 0
            bVal = Number(bVal) || 0
            if (aVal < bVal) {
              return sortDirection.value === 'asc' ? -1 : 1
            }
            if (aVal > bVal) {
              return sortDirection.value === 'asc' ? 1 : -1
            }
            return 0
          }

          // Convert to string for comparison
          aVal = String(aVal).toLowerCase()
          bVal = String(bVal).toLowerCase()

          if (aVal < bVal) {
            return sortDirection.value === 'asc' ? -1 : 1
          }
          if (aVal > bVal) {
            return sortDirection.value === 'asc' ? 1 : -1
          }
          return 0
        })

        return sorted
      } catch (error) {
        console.error('Error sorting users:', error)
        return users.value
      }
    })

    const sortedGroups = computed(() => {
      if (!groups.value || !Array.isArray(groups.value)) {
        return []
      }
      return [...groups.value].sort((a, b) => {
        const aName = (a.groupName || '').toLowerCase()
        const bName = (b.groupName || '').toLowerCase()
        return aName.localeCompare(bName)
      })
    })

    const filteredUsers = computed(() => {
      if (selectedCompany.value === 'all') {
        return sortedUsers.value
      }
      const companyId = Number(selectedCompany.value)
      return sortedUsers.value.filter(user => user.groupId === companyId)
    })

    const totalPages = computed(() => {
      return Math.max(1, Math.ceil(filteredUsers.value.length / itemsPerPage.value))
    })

    const paginatedUsers = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return filteredUsers.value.slice(start, end)
    })

    const onCompanyFilterChange = () => {
      currentPage.value = 1 // Reset to first page when filter changes
    }

    const resetCompanyFilter = () => {
      selectedCompany.value = 'all'
      currentPage.value = 1 // Reset to first page
    }

    const goToFirstPage = () => {
      currentPage.value = 1
    }

    const goToPrevPage = () => {
      if (currentPage.value > 1) {
        currentPage.value--
      }
    }

    const goToNextPage = () => {
      if (currentPage.value < totalPages.value) {
        currentPage.value++
      }
    }

    const goToLastPage = () => {
      currentPage.value = totalPages.value
    }

    watch([sortColumn, sortDirection], ([column, direction]) => {
      localStorage.setItem('usersSortColumn', column)
      localStorage.setItem('usersSortDirection', direction)
      currentPage.value = 1 // Reset to first page when sorting changes
    })

    watch(totalPages, (newTotalPages) => {
      // Reset to page 1 if current page exceeds total pages (e.g., after deletion)
      if (currentPage.value > newTotalPages && newTotalPages > 0) {
        currentPage.value = 1
      }
    })

    watch(() => route.query.companyId, (companyId) => {
      if (companyId) {
        selectedCompany.value = companyId
        currentPage.value = 1 // Reset to first page when filter changes
      } else {
        selectedCompany.value = 'all'
      }
    })

    const closeModal = () => {
      showAddForm.value = false
      editingUser.value = null
      formData.value = {
        name: '',
        userCode: '',
        groupId: 0,
      }
      userCodeError.value = ''
    }

    const handleEscapeKey = (event) => {
      if (event.key === 'Escape') {
        if (showAddForm.value || editingUser.value) {
          closeModal()
        } else if (deletingUser.value) {
          closeDeleteModal()
        }
      }
    }

    watch([showAddForm, editingUser, deletingUser], ([showAdd, editing, deleting]) => {
      if (showAdd || editing || deleting) {
        // Focus the name input when edit modal opens
        if (showAdd || editing) {
          setTimeout(() => {
            if (nameInput.value) {
              nameInput.value.focus()
            }
          }, 100)
        }
        // Add escape key listener
        document.addEventListener('keydown', handleEscapeKey)
      } else {
        // Remove escape key listener when modal closes
        document.removeEventListener('keydown', handleEscapeKey)
      }
    })

    onUnmounted(() => {
      // Clean up event listener on component unmount
      document.removeEventListener('keydown', handleEscapeKey)
    })

    onMounted(async () => {
      await loadGroups()
      loadUsers()
    })

    return {
      users,
      groups,
      loading,
      error,
      showAddForm,
      editingUser,
      deletingUser,
      saving,
      formData,
      editUser,
      viewUserWeek,
      deleteUser,
      confirmDelete,
      closeDeleteModal,
      saveUser,
      closeModal,
      sortBy,
      sortedUsers,
      paginatedUsers,
      totalPages,
      currentPage,
      goToFirstPage,
      goToPrevPage,
      goToNextPage,
      goToLastPage,
      sortColumn,
      sortDirection,
      selectedCompany,
      sortedGroups,
      filteredUsers,
      onCompanyFilterChange,
      resetCompanyFilter,
      getGroupName,
      nameInput,
      addEditModalContent,
      deleteModalContent,
      addEditModalStyle,
      deleteModalStyle,
      startDrag,
      openAddForm,
      userCodeError,
      clearUserCodeError,
      handleOverlayMouseDown
    }
  },
}
</script>

<style scoped>
.users {
  padding: 20px;
}

.actions {
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 30px;
}

.actions > button {
  width: 170px;
}

.company-filter {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-left: -20px;
}

.company-filter label {
  font-weight: bold;
  color: #333;
}

.company-filter select {
  padding: 8px 12px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: white;
  font-size: 14px;
  cursor: pointer;
}

.company-filter-btn {
  padding: 8px 10px;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #e0e0e0;
  color: #333;
  font-size: 14px;
  cursor: pointer;
  min-width: auto;
  width: auto;
}

.company-filter-btn:hover {
  background-color: #d0d0d0;
}

.pagination-arrows {
  display: flex;
  gap: 5px;
  align-items: center;
}

.name-arrow-btn {
  background-color: #e0e0e0;
  border: 1px solid #ccc;
  border-radius: 4px;
  color: green;
  font-size: 18px;
  cursor: pointer;
  padding: 0 5px;
  margin-left: 8px;
  display: inline-block;
}

.name-arrow-btn:hover {
  background-color: #d0d0d0;
  color: darkgreen;
}

.page-info-wrapper {
  border: 0;
  width: 100px;
  text-align: center;
}

.page-info {
  padding: 0;
  font-size: 14px;
  color: #333;
}

.pagination-arrow {
  padding: 8px 12px;
  cursor: pointer;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #007bff;
  color: white;
  font-size: 20px;
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  min-width: 40px;
}

.pagination-arrow:hover:not(:disabled) {
  background-color: #0056b3;
}

.pagination-arrow:disabled {
  background-color: #ccc;
  cursor: not-allowed;
  opacity: 0.6;
}

button {
  padding: 8px 16px;
  cursor: pointer;
  border: 1px solid #ccc;
  border-radius: 4px;
  background-color: #007bff;
  color: white;
}

button:hover {
  background-color: #0056b3;
}

.error-message {
  color: red;
  padding: 10px;
  background-color: #ffe6e6;
  border-radius: 4px;
  margin-bottom: 20px;
}

.no-records {
  text-align: center;
  padding: 20px;
  color: #666;
  font-style: italic;
}

.users-table {
  width: 100%;
  max-width: 800px;
  border-collapse: collapse;
  margin-top: 20px;
  table-layout: fixed;
}

.users-table th,
.users-table td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
  height: 50px;
  min-height: 50px;
  max-height: 50px;
  box-sizing: border-box;
  vertical-align: middle;
}

.users-table th.sortable {
  cursor: pointer;
  user-select: none;
  position: relative;
}

.users-table th.sortable:hover {
  background-color: #e0e0e0;
}

.users-table th.sorted-column {
  background-color: #f5f5f5;
}

.users-table th.sorted-column:hover {
  background-color: #e8e8e8;
}

.sort-indicator {
  margin-left: 5px;
  font-weight: bold;
  color: #007bff;
  visibility: hidden;
  display: inline-block;
  width: 15px;
  text-align: center;
}

.sort-indicator-visible {
  visibility: visible;
}

.company-column {
  text-align: left;
  width: 200px;
  max-width: 200px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.id-column {
  width: 60px;
  min-width: 60px;
}

.code-column {
  width: 100px;
  min-width: 100px;
}

.name-column {
  width: 200px;
  max-width: 200px;
  overflow: hidden;
}

.name-column .name-text {
  display: inline-block;
  max-width: calc(100% - 35px);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  vertical-align: middle;
}

.name-column .name-arrow-btn {
  display: inline-block;
  vertical-align: middle;
  flex-shrink: 0;
}

.actions-column {
  width: 200px;
  min-width: 200px;
}

.users-table td.action-buttons {
  /* Ensure this cell has exactly the same styling as other cells */
  height: 50px;
  min-height: 50px;
  max-height: 50px;
  padding: 8px;
  border: 1px solid #ccc;
  box-sizing: border-box;
  vertical-align: middle;
  text-align: left;
}

.action-buttons {
  display: flex;
  gap: 5px;
  white-space: nowrap;
  width: auto;
  align-items: center;
  justify-content: flex-start;
  margin: 0;
  padding: 0;
  height: 100%;
}

.action-cell-content {
  border: 0;
  height: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 20px;
}

.x-button {
  padding: 7px 14px;
  font-size: 14px;
  line-height: 1;
  margin: 0;
  height: auto;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: 1px solid #ccc;
  border-radius: 4px;
  cursor: pointer;
  background-color: #f8f9fa;
  color: #333;
}

.x-button:hover {
  background-color: #e9ecef;
}

.edit-action-btn {
  background-color: #28a745;
  color: white;
}

.edit-action-btn:hover {
  background-color: #218838;
}

.delete-action-btn {
  background-color: #dc3545;
  color: white;
}

.delete-action-btn:hover {
  background-color: #c82333;
}

.edit-btn,
.delete-btn {
  padding: 4px 10px;
  font-size: 14px;
  line-height: normal;
  margin: 0;
  height: auto;
  display: inline-flex;
  align-items: center;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  color: white;
}

.edit-btn {
  background-color: #28a745;
}

.edit-btn:hover {
  background-color: #218838;
}

.delete-btn {
  background-color: #dc3545;
}

.delete-btn:hover {
  background-color: #c82333;
}

.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 1000;
}

.modal-content {
  background: white;
  padding: 20px;
  border-radius: 8px;
  min-width: 400px;
  max-width: 500px;
  position: relative;
  z-index: 1001;
  transition: transform 0s;
}

.modal-title-box {
  background-color: #f0f0f0;
  padding: 15px;
  margin: -20px -20px 20px -20px;
  border-radius: 8px 8px 0 0;
  width: calc(100% + 40px);
}

.modal-content h3 {
  font-size: 24px;
  font-weight: bold;
  text-align: center;
  margin: 0;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
}

.label-with-error {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-bottom: 5px;
}

.label-with-error label {
  margin-bottom: 0;
}

.user-code-error {
  color: #d32f2f;
  font-size: 13px;
  font-weight: normal;
}

.form-group input {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  box-sizing: border-box;
}

.form-group select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ccc;
  border-radius: 4px;
  box-sizing: border-box;
}

.form-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
  margin-top: 20px;
}

.form-actions button[type="button"] {
  background-color: #6c757d;
}

.form-actions button[type="button"]:hover {
  background-color: #5a6268;
}

.delete-confirmation-content {
  padding: 20px 0;
  text-align: center;
}

.delete-confirmation-content p {
  margin: 10px 0;
  font-size: 16px;
}

.warning-text {
  color: #dc3545;
  font-weight: bold;
}

.delete-confirm-btn {
  background-color: #dc3545 !important;
  color: white !important;
}

.delete-confirm-btn:hover {
  background-color: #c82333 !important;
}
</style>
