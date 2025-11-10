<template>
  <div class="groups">
    <div class="actions">
      <button id="add-company-btn" name="add-company-btn" @click="openAddForm">Add Company</button>
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
    </div>

    <div v-if="loading">Loading...</div>
    <div v-else-if="error" class="error-message">{{ error }}</div>
    <table v-else class="groups-table">
      <thead>
        <tr>
          <th @click="sortBy('id')" class="sortable id-column" :class="{ 'sorted-column': sortColumn === 'id' }">
            ID
            <span class="sort-indicator" :class="{ 'sort-indicator-visible': sortColumn === 'id' }">
              {{ sortColumn === 'id' && sortDirection === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
          <th @click="sortBy('groupName')" class="sortable" :class="{ 'sorted-column': sortColumn === 'groupName' }">
            Company Name
            <span class="sort-indicator" :class="{ 'sort-indicator-visible': sortColumn === 'groupName' }">
              {{ sortColumn === 'groupName' && sortDirection === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
          <th class="user-count-column">#</th>
          <th @click="sortBy('weekStartDOW')" class="sortable start-dow-column" :class="{ 'sorted-column': sortColumn === 'weekStartDOW' }">
            Start DOW
            <span class="sort-indicator" :class="{ 'sort-indicator-visible': sortColumn === 'weekStartDOW' }">
              {{ sortColumn === 'weekStartDOW' && sortDirection === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
          <th @click="sortBy('weekStartTime')" class="sortable start-time-column" :class="{ 'sorted-column': sortColumn === 'weekStartTime' }">
            Start Time
            <span class="sort-indicator" :class="{ 'sort-indicator-visible': sortColumn === 'weekStartTime' }">
              {{ sortColumn === 'weekStartTime' && sortDirection === 'asc' ? '↑' : '↓' }}
            </span>
          </th>
          <th class="actions-column">Actions</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="group in paginatedGroups" :key="group.id">
          <td class="id-column" style="text-align: center;">{{ group.id }}</td>
          <td>
            <span>{{ group.groupName }}</span>
            <button :id="`view-company-btn-${group.id}`" :name="`view-company-btn-${group.id}`" @click="viewCompany(group)" class="name-arrow-btn" title="View Employees">▶</button>
          </td>
          <td class="user-count-column" style="text-align: center;">{{ getUserCount(group.id) }}</td>
          <td class="start-dow-column">{{ group.weekStartDOW }}</td>
          <td class="start-time-column">{{ group.weekStartTime }}</td>
          <td class="actions-column"><div class="action-cell-content">
            <button :id="`edit-company-btn-${group.id}`" :name="`edit-company-btn-${group.id}`" class="x-button edit-action-btn" @click="editGroup(group)">Edit</button>
            <button :id="`delete-company-btn-${group.id}`" :name="`delete-company-btn-${group.id}`" class="x-button delete-action-btn" @click="deleteGroup(group.id)">Delete</button>
          </div></td>
        </tr>
      </tbody>
    </table>

    <!-- Add/Edit Modal -->
    <div v-if="showAddForm || editingGroup" class="modal-overlay" @mousedown="handleOverlayMouseDown($event, 'addEdit')">
      <div ref="addEditModalContent" class="modal-content" :style="addEditModalStyle" @click.stop>
        <div class="modal-title-box" @mousedown="startDrag($event, 'addEdit')" style="cursor: move;">
          <h3>{{ editingGroup ? 'Edit Company' : 'Add Company' }}</h3>
        </div>
        <form @submit.prevent="saveGroup">
          <div class="form-group">
            <label for="group-name">Group Name:</label>
            <input id="group-name" name="group-name" ref="groupNameInput" v-model="formData.groupName" required />
          </div>
          <div class="form-group">
            <label for="week-start-dow">Week Start Day of Week:</label>
            <select id="week-start-dow" name="week-start-dow" v-model="formData.weekStartDOW" required>
              <option value="Monday">Monday</option>
              <option value="Tuesday">Tuesday</option>
              <option value="Wednesday">Wednesday</option>
              <option value="Thursday">Thursday</option>
              <option value="Friday">Friday</option>
              <option value="Saturday">Saturday</option>
              <option value="Sunday">Sunday</option>
            </select>
          </div>
          <div class="form-group">
            <label for="group-hours">Week Start Time:</label>
            <div class="time-widget-wrapper">
              <div class="time-input-group">
                <div class="time-inputs">
                  <div class="time-input-wrapper">
                    <input 
                      id="group-hours"
                      name="group-hours"
                      v-model="timeHoursDisplay"
                      @input="handleTimeInput('hours', $event)"
                      @focus="handleTimeInputFocus($event)"
                      @blur="handleTimeInputBlur($event, 'hours')"
                      @keydown="handleTimeInputKeydown($event, 'hours')"
                      type="text"
                      pattern="[0-9]{1,2}"
                      placeholder="HH"
                      required 
                      class="time-input-small"
                      maxlength="2"
                    />
                    <div class="time-input-buttons">
                      <button type="button" @click="incrementTime('hours')" class="time-btn-up" tabindex="-1">▲</button>
                      <button type="button" @click="decrementTime('hours')" class="time-btn-down" tabindex="-1">▼</button>
                    </div>
                  </div>
                  <span class="time-separator">:</span>
                  <div class="time-input-wrapper">
                    <input 
                      id="group-minutes"
                      name="group-minutes"
                      v-model="timeMinutesDisplay"
                      @input="handleTimeInput('minutes', $event)"
                      @focus="handleTimeInputFocus($event)"
                      @blur="handleTimeInputBlur($event, 'minutes')"
                      @keydown="handleTimeInputKeydown($event, 'minutes')"
                      type="text"
                      pattern="[0-9]{1,2}"
                      placeholder="MM"
                      required 
                      class="time-input-small"
                      maxlength="2"
                    />
                    <div class="time-input-buttons">
                      <button type="button" @click="incrementTime('minutes')" class="time-btn-up" tabindex="-1">▲</button>
                      <button type="button" @click="decrementTime('minutes')" class="time-btn-down" tabindex="-1">▼</button>
                    </div>
                  </div>
                  <span class="time-separator">:</span>
                  <div class="time-input-wrapper">
                    <input 
                      id="group-seconds"
                      name="group-seconds"
                      v-model="timeSecondsDisplay"
                      @input="handleTimeInput('seconds', $event)"
                      @focus="handleTimeInputFocus($event)"
                      @blur="handleTimeInputBlur($event, 'seconds')"
                      @keydown="handleTimeInputKeydown($event, 'seconds')"
                      type="text"
                      pattern="[0-9]{1,2}"
                      placeholder="SS"
                      required 
                      class="time-input-small"
                      maxlength="2"
                    />
                    <div class="time-input-buttons">
                      <button type="button" @click="incrementTime('seconds')" class="time-btn-up" tabindex="-1">▲</button>
                      <button type="button" @click="decrementTime('seconds')" class="time-btn-down" tabindex="-1">▼</button>
                    </div>
                  </div>
                  <select id="group-ampm" name="group-ampm" v-model="timeAMPM" @change="updateTimeFromInputs" class="time-am-pm">
                    <option value="AM">AM</option>
                    <option value="PM">PM</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="form-actions">
            <button id="save-company-btn" name="save-company-btn" type="submit" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
            <button id="cancel-company-btn" name="cancel-company-btn" type="button" @click="closeModal">Cancel</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deletingGroup" class="modal-overlay" @mousedown="handleOverlayMouseDown($event, 'delete')">
      <div ref="deleteModalContent" class="modal-content" :style="deleteModalStyle" @click.stop>
        <div class="modal-title-box" @mousedown="startDrag($event, 'delete')" style="cursor: move;">
          <h3>Delete Company</h3>
        </div>
        <div class="delete-confirmation-content">
          <p>Are you sure you want to delete <strong>{{ deletingGroup.groupName }}</strong>?</p>
          <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="form-actions">
          <button id="confirm-delete-company-btn" name="confirm-delete-company-btn" type="button" @click="confirmDelete" class="delete-confirm-btn">Delete</button>
          <button id="cancel-delete-company-btn" name="cancel-delete-company-btn" type="button" @click="closeDeleteModal">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, computed, watch, onUnmounted, nextTick } from 'vue'
import { useRouter } from 'vue-router'
import api from '../api/index.js'

export default {
  name: 'Groups',
  setup() {
    const router = useRouter()
    const groups = ref([])
    const users = ref([])
    const loading = ref(false)
    const error = ref('')
    const showAddForm = ref(false)
    const editingGroup = ref(null)
    const deletingGroup = ref(null)
    const saving = ref(false)
    const formData = ref({
      groupName: '',
      weekStartDOW: 'Sunday',
      weekStartTime: '',
    })
    const timeHours = ref(12)
    const timeMinutes = ref(0)
    const timeSeconds = ref(0)
    const timeAMPM = ref('AM')
    
    // Track raw input values for display (to prevent immediate formatting)
    const timeHoursDisplay = ref('12')
    const timeMinutesDisplay = ref('00')
    const timeSecondsDisplay = ref('00')
    const sortColumn = ref(localStorage.getItem('groupsSortColumn') || 'id')
    const sortDirection = ref(localStorage.getItem('groupsSortDirection') || 'asc')
    const groupNameInput = ref(null)
    const currentPage = ref(1)
    const itemsPerPage = ref(5)
    
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
      loading.value = true
      error.value = ''
      try {
        const response = await api.groups.getAll()
        groups.value = response.data.data || []
      } catch (err) {
        error.value = err.response?.data?.error || err.message || 'Error loading groups'
        console.error('Error loading groups:', err)
      } finally {
        loading.value = false
      }
    }

    const loadUsers = async () => {
      try {
        const response = await api.users.getAll()
        users.value = response.data.data || []
      } catch (err) {
        console.error('Error loading users:', err)
      }
    }

    const getUserCount = (groupId) => {
      if (!groupId) return 0
      return users.value.filter(user => user.groupId === groupId).length
    }

    const openAddForm = () => {
      resetModalPositions()
      showAddForm.value = true
    }
    
    const editGroup = async (group) => {
      resetModalPositions()
      editingGroup.value = group
      formData.value = {
        groupName: group.groupName,
        weekStartDOW: group.weekStartDOW || 'Sunday',
        weekStartTime: group.weekStartTime,
      }
      // Wait for modal to render before parsing time
      await nextTick()
      // Parse the 24-hour time into separate fields
      if (group.weekStartTime) {
        parseTimeFrom24Hour(group.weekStartTime)
      } else {
        // Default to 12:00:00 AM if no time is set
        parseTimeFrom24Hour('00:00:00')
      }
    }

    const viewCompany = (group) => {
      router.push({
        path: '/users',
        query: { companyId: group.id }
      })
    }

    const deleteGroup = async (id) => {
      resetModalPositions()
      const group = groups.value.find(g => g.id === id)
      deletingGroup.value = group
    }

    const confirmDelete = async () => {
      if (!deletingGroup.value) return
      
      const id = deletingGroup.value.id
      try {
        await api.groups.delete(id)
        await loadGroups()
        closeDeleteModal()
      } catch (err) {
        alert('Error deleting group: ' + (err.response?.data?.error || err.message))
      }
    }

    const closeDeleteModal = () => {
      deletingGroup.value = null
    }

    const saveGroup = async () => {
      // Ensure time is set before saving
      if (!formData.value.weekStartTime) {
        updateTimeFromInputs()
        // If still empty after update, set default
        if (!formData.value.weekStartTime) {
          formData.value.weekStartTime = '00:00:00'
        }
      }
      
      saving.value = true
      try {
        if (editingGroup.value) {
          await api.groups.update(editingGroup.value.id, formData.value)
        } else {
          await api.groups.create(formData.value)
        }
        await loadGroups()
        closeModal()
      } catch (err) {
        alert('Error saving group: ' + (err.response?.data?.error || err.response?.data?.message || err.message))
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

    const sortedGroups = computed(() => {
      if (!groups.value || !Array.isArray(groups.value)) {
        return []
      }
      
      if (!sortColumn.value) {
        return groups.value
      }

      try {
        const sorted = [...groups.value].sort((a, b) => {
          let aVal = a[sortColumn.value]
          let bVal = b[sortColumn.value]

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
        console.error('Error sorting groups:', error)
        return groups.value
      }
    })

    const totalPages = computed(() => {
      return Math.max(1, Math.ceil(sortedGroups.value.length / itemsPerPage.value))
    })

    const paginatedGroups = computed(() => {
      const start = (currentPage.value - 1) * itemsPerPage.value
      const end = start + itemsPerPage.value
      return sortedGroups.value.slice(start, end)
    })

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
      localStorage.setItem('groupsSortColumn', column)
      localStorage.setItem('groupsSortDirection', direction)
      currentPage.value = 1 // Reset to first page when sorting changes
    })

    watch(totalPages, (newTotalPages) => {
      // Reset to page 1 if current page exceeds total pages (e.g., after deletion)
      if (currentPage.value > newTotalPages && newTotalPages > 0) {
        currentPage.value = 1
      }
    })

    const formatTwoDigits = (value) => {
      if (value === null || value === undefined || value === '') {
        return '00'
      }
      return String(value).padStart(2, '0')
    }

    const incrementTime = (field) => {
      if (field === 'hours') {
        timeHours.value = Math.min(12, (timeHours.value || 12) + 1)
        timeHoursDisplay.value = formatTwoDigits(timeHours.value)
      } else if (field === 'minutes') {
        timeMinutes.value = Math.min(59, (timeMinutes.value || 0) + 1)
        timeMinutesDisplay.value = formatTwoDigits(timeMinutes.value)
      } else if (field === 'seconds') {
        timeSeconds.value = Math.min(59, (timeSeconds.value || 0) + 1)
        timeSecondsDisplay.value = formatTwoDigits(timeSeconds.value)
      }
      updateTimeFromInputs()
    }

    const decrementTime = (field) => {
      if (field === 'hours') {
        timeHours.value = Math.max(1, (timeHours.value || 12) - 1)
        timeHoursDisplay.value = formatTwoDigits(timeHours.value)
      } else if (field === 'minutes') {
        timeMinutes.value = Math.max(0, (timeMinutes.value || 0) - 1)
        timeMinutesDisplay.value = formatTwoDigits(timeMinutes.value)
      } else if (field === 'seconds') {
        timeSeconds.value = Math.max(0, (timeSeconds.value || 0) - 1)
        timeSecondsDisplay.value = formatTwoDigits(timeSeconds.value)
      }
      updateTimeFromInputs()
    }

    const handleTimeInput = (field, event) => {
      const value = event.target.value.replace(/[^0-9]/g, '') // Remove non-numeric characters
      
      // Update display value to show what user is typing
      if (field === 'hours') {
        timeHoursDisplay.value = value
      } else if (field === 'minutes') {
        timeMinutesDisplay.value = value
      } else if (field === 'seconds') {
        timeSecondsDisplay.value = value
      }
      
      // If the value is a single digit (0-9), allow it temporarily without formatting
      // This allows users to type "12" by typing "1" then "2"
      if (value.length === 1 && /^[0-9]$/.test(value)) {
        // Store the raw value temporarily
        const digit = parseInt(value)
        if (field === 'hours') {
          timeHours.value = digit
        } else if (field === 'minutes') {
          timeMinutes.value = digit
        } else if (field === 'seconds') {
          timeSeconds.value = digit
        }
        // Don't format yet - let user continue typing
        updateTimeFromInputs()
        return
      }
      
      // Format when value is empty, 2 digits, or invalid
      if (field === 'hours') {
        const numValue = value === '' ? 12 : parseInt(value) || 12
        timeHours.value = Math.max(1, Math.min(12, numValue))
        timeHoursDisplay.value = formatTwoDigits(timeHours.value)
      } else if (field === 'minutes') {
        const numValue = value === '' ? 0 : parseInt(value) || 0
        timeMinutes.value = Math.max(0, Math.min(59, numValue))
        timeMinutesDisplay.value = formatTwoDigits(timeMinutes.value)
      } else if (field === 'seconds') {
        const numValue = value === '' ? 0 : parseInt(value) || 0
        timeSeconds.value = Math.max(0, Math.min(59, numValue))
        timeSecondsDisplay.value = formatTwoDigits(timeSeconds.value)
      }
      
      updateTimeFromInputs()
    }
    
    const handleTimeInputBlur = (event, field) => {
      // When user leaves the field, ensure it's formatted
      const value = event.target.value.replace(/[^0-9]/g, '')
      if (value === '') {
        // Set default if empty
        if (field === 'hours') {
          timeHours.value = 12
          timeHoursDisplay.value = '12'
        } else if (field === 'minutes') {
          timeMinutes.value = 0
          timeMinutesDisplay.value = '00'
        } else if (field === 'seconds') {
          timeSeconds.value = 0
          timeSecondsDisplay.value = '00'
        }
        updateTimeFromInputs()
      } else {
        // Process the value normally to ensure formatting
        handleTimeInput(field, event)
      }
    }
    
    const handleTimeInputFocus = (event) => {
      // Select all text when input is focused
      event.target.select()
    }
    
    const handleTimeInputKeydown = (event, field) => {
      // If user presses a number key and the input still has the formatted value, clear it first
      const key = event.key
      if (/[0-9]/.test(key)) {
        const currentValue = event.target.value
        const selectionStart = event.target.selectionStart
        const selectionEnd = event.target.selectionEnd
        const isAllSelected = selectionStart === 0 && selectionEnd === currentValue.length
        
        let formattedValue = ''
        
        if (field === 'hours') {
          formattedValue = formatTwoDigits(timeHours.value)
        } else if (field === 'minutes') {
          formattedValue = formatTwoDigits(timeMinutes.value)
        } else if (field === 'seconds') {
          formattedValue = formatTwoDigits(timeSeconds.value)
        }
        
        // If all text is selected or the current value matches the formatted value exactly, clear it first
        if (isAllSelected || currentValue === formattedValue) {
          // Prevent the default key behavior
          event.preventDefault()
          // Clear both the input and reactive values
          if (field === 'hours') {
            timeHours.value = ''
            timeHoursDisplay.value = ''
          } else if (field === 'minutes') {
            timeMinutes.value = ''
            timeMinutesDisplay.value = ''
          } else if (field === 'seconds') {
            timeSeconds.value = ''
            timeSecondsDisplay.value = ''
          }
          // Manually insert the key that was pressed
          event.target.value = key
          // Update display value
          if (field === 'hours') {
            timeHoursDisplay.value = key
          } else if (field === 'minutes') {
            timeMinutesDisplay.value = key
          } else if (field === 'seconds') {
            timeSecondsDisplay.value = key
          }
          // Update reactive value to the raw digit (not formatted)
          if (field === 'hours') {
            timeHours.value = parseInt(key)
          } else if (field === 'minutes') {
            timeMinutes.value = parseInt(key)
          } else if (field === 'seconds') {
            timeSeconds.value = parseInt(key)
          }
          // Set cursor position after the inserted character
          setTimeout(() => {
            event.target.setSelectionRange(1, 1)
          }, 0)
        }
      }
    }

    const formatTimeInput = (field) => {
      if (field === 'hours') {
        if (timeHours.value !== null && timeHours.value !== undefined && timeHours.value !== '') {
          // Keep as-is for hours (1-12), but ensure it's a number
          timeHours.value = parseInt(timeHours.value) || 12
        }
      } else if (field === 'minutes') {
        if (timeMinutes.value !== null && timeMinutes.value !== undefined && timeMinutes.value !== '') {
          const val = parseInt(timeMinutes.value) || 0
          timeMinutes.value = Math.max(0, Math.min(59, val))
        } else {
          timeMinutes.value = 0
        }
      } else if (field === 'seconds') {
        if (timeSeconds.value !== null && timeSeconds.value !== undefined && timeSeconds.value !== '') {
          const val = parseInt(timeSeconds.value) || 0
          timeSeconds.value = Math.max(0, Math.min(59, val))
        } else {
          timeSeconds.value = 0
        }
      }
      updateTimeFromInputs()
    }

    const updateTimeFromInputs = () => {
      // Validate inputs - allow empty values during input
      if (timeHours.value === null || timeHours.value === undefined || timeHours.value === '') {
        return
      }
      if (timeHours.value < 1 || timeHours.value > 12) {
        return
      }
      if (timeMinutes.value === null || timeMinutes.value === undefined || timeMinutes.value === '') {
        return
      }
      if (timeMinutes.value < 0 || timeMinutes.value > 59) {
        return
      }
      if (timeSeconds.value === null || timeSeconds.value === undefined || timeSeconds.value === '') {
        return
      }
      if (timeSeconds.value < 0 || timeSeconds.value > 59) {
        return
      }

      // Convert to 24-hour format
      let hours24 = timeHours.value
      if (timeAMPM.value === 'PM' && hours24 !== 12) {
        hours24 += 12
      } else if (timeAMPM.value === 'AM' && hours24 === 12) {
        hours24 = 0
      }

      // Format as HH:MM:SS
      formData.value.weekStartTime = `${String(hours24).padStart(2, '0')}:${String(timeMinutes.value).padStart(2, '0')}:${String(timeSeconds.value).padStart(2, '0')}`
    }

    const parseTimeFrom24Hour = (time24) => {
      if (!time24 || time24.trim() === '') {
        timeHours.value = 12
        timeMinutes.value = 0
        timeSeconds.value = 0
        timeAMPM.value = 'AM'
        timeHoursDisplay.value = '12'
        timeMinutesDisplay.value = '00'
        timeSecondsDisplay.value = '00'
        return
      }

      // Handle different time formats (HH:MM:SS or HH:MM)
      const parts = time24.trim().split(':')
      if (parts.length >= 2) {
        let hours24 = parseInt(parts[0]) || 0
        const minutes = parseInt(parts[1]) || 0
        const seconds = parseInt(parts[2]) || 0

        // Convert to 12-hour format
        if (hours24 === 0) {
          timeHours.value = 12
          timeAMPM.value = 'AM'
        } else if (hours24 === 12) {
          timeHours.value = 12
          timeAMPM.value = 'PM'
        } else if (hours24 > 12) {
          timeHours.value = hours24 - 12
          timeAMPM.value = 'PM'
        } else {
          timeHours.value = hours24
          timeAMPM.value = 'AM'
        }

        timeMinutes.value = minutes
        timeSeconds.value = seconds
        timeHoursDisplay.value = formatTwoDigits(timeHours.value)
        timeMinutesDisplay.value = formatTwoDigits(timeMinutes.value)
        timeSecondsDisplay.value = formatTwoDigits(timeSeconds.value)
      } else {
        // Invalid format, default to 12:00:00 AM
        timeHours.value = 12
        timeMinutes.value = 0
        timeSeconds.value = 0
        timeAMPM.value = 'AM'
        timeHoursDisplay.value = '12'
        timeMinutesDisplay.value = '00'
        timeSecondsDisplay.value = '00'
      }
    }

    const closeModal = () => {
      showAddForm.value = false
      editingGroup.value = null
      formData.value = {
        groupName: '',
        weekStartDOW: 'Sunday',
        weekStartTime: '',
      }
      timeHours.value = 12
      timeMinutes.value = 0
      timeSeconds.value = 0
      timeAMPM.value = 'AM'
      timeHoursDisplay.value = '12'
      timeMinutesDisplay.value = '00'
      timeSecondsDisplay.value = '00'
    }

    const handleEscapeKey = (event) => {
      if (event.key === 'Escape') {
        if (showAddForm.value || editingGroup.value) {
          closeModal()
        } else if (deletingGroup.value) {
          closeDeleteModal()
        }
      }
    }

    watch([showAddForm, editingGroup, deletingGroup], ([showAdd, editing, deleting]) => {
      if (showAdd || editing || deleting) {
        // Focus the group name input when edit modal opens
        if (showAdd || editing) {
          setTimeout(() => {
            if (groupNameInput.value) {
              groupNameInput.value.focus()
            }
          }, 100)
        }
        // Initialize time if adding new group
        if (showAdd && !editing) {
          // Set default time values
          timeHours.value = 12
          timeMinutes.value = 0
          timeSeconds.value = 0
          timeAMPM.value = 'AM'
          timeHoursDisplay.value = '12'
          timeMinutesDisplay.value = '00'
          timeSecondsDisplay.value = '00'
          // Update formData with default time
          updateTimeFromInputs()
        } else if (editing && !showAdd) {
          // When editing, ensure time is parsed after modal renders
          nextTick(() => {
            if (editingGroup.value && editingGroup.value.weekStartTime) {
              parseTimeFrom24Hour(editingGroup.value.weekStartTime)
            }
          })
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

    onMounted(() => {
      loadGroups()
      loadUsers()
    })

    return {
      groups,
      loading,
      error,
      showAddForm,
      editingGroup,
      deletingGroup,
      saving,
      formData,
      editGroup,
      viewCompany,
      deleteGroup,
      confirmDelete,
      closeDeleteModal,
      saveGroup,
      closeModal,
      sortBy,
      sortedGroups,
      paginatedGroups,
      totalPages,
      currentPage,
      goToFirstPage,
      goToPrevPage,
      goToNextPage,
      goToLastPage,
      sortColumn,
      sortDirection,
      getUserCount,
      groupNameInput,
      timeHours,
      timeMinutes,
      timeSeconds,
      timeAMPM,
      timeHoursDisplay,
      timeMinutesDisplay,
      timeSecondsDisplay,
      updateTimeFromInputs,
      parseTimeFrom24Hour,
      formatTimeInput,
      formatTwoDigits,
      handleTimeInput,
      incrementTime,
      decrementTime,
      addEditModalContent,
      deleteModalContent,
      addEditModalStyle,
      deleteModalStyle,
      startDrag,
      openAddForm,
      handleTimeInputFocus,
      handleTimeInputKeydown,
      handleTimeInputBlur,
      handleOverlayMouseDown
    }
  },
}
</script>

<style scoped>
.groups {
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

.pagination-arrows {
  display: flex;
  gap: 5px;
  align-items: center;
}

.page-info-wrapper {
  border: 0;
  width: 100px;
  text-align: center;
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

.groups-table {
  width: 100%;
  max-width: 800px;
  border-collapse: collapse;
  margin-top: 20px;
  table-layout: fixed;
}

.groups-table th,
.groups-table td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
  height: 50px;
  min-height: 50px;
  max-height: 50px;
  box-sizing: border-box;
  vertical-align: middle;
}

.groups-table th.sortable {
  cursor: pointer;
  user-select: none;
  position: relative;
}

.groups-table th.sortable:hover {
  background-color: #e0e0e0;
}

.groups-table th.sorted-column {
  background-color: #f5f5f5;
}

.groups-table th.sorted-column:hover {
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

.id-column {
  width: 60px;
  min-width: 60px;
}

.actions-column {
  width: 200px;
  min-width: 200px;
}

.user-count-column {
  width: 30px;
  min-width: 30px;
}

.start-dow-column {
  width: 130px;
  min-width: 130px;
}

.start-time-column {
  width: 130px;
  min-width: 130px;
}

.time-form-group-wrapper {
  display: inline-block !important;
  width: auto !important;
}

.time-form-group-wrapper .time-input-group {
  display: inline-block !important;
  width: auto !important;
}

.time-form-group-wrapper .time-inputs {
  display: inline-flex !important;
  align-items: center;
  gap: 5px;
  width: auto !important;
}

.groups-table td.action-buttons {
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
.update-btn,
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

.edit-btn,
.update-btn {
  background-color: #28a745;
}

.edit-btn:hover,
.update-btn:hover {
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
  border: 1px solid #d0d0d0;
  border-radius: 4px;
  box-sizing: border-box;
  background-color: white;
}

.form-group .time-am-pm {
  width: 70px !important;
  min-width: 70px;
  max-width: 70px;
  padding: 11px 2px;
}

.time-widget-wrapper {
  border: 0;
  padding: 0;
  display: inline-block;
  width: 300px;
}

.time-input-group {
  display: flex;
  flex-direction: column;
  gap: 5px;
}

.time-inputs {
  display: flex;
  align-items: center;
  gap: 5px;
}

.time-input-wrapper {
  display: flex;
  flex-direction: column;
  position: relative;
}

.time-input-small {
  width: 60px;
  padding: 8px;
  border: 1px solid #d0d0d0;
  border-radius: 4px;
  box-sizing: border-box;
  background-color: white;
  text-align: center;
}

.time-input-buttons {
  display: flex;
  flex-direction: column;
  position: absolute;
  right: 2px;
  top: 2px;
  bottom: 2px;
  width: 16px;
  pointer-events: none;
}

.time-btn-up,
.time-btn-down {
  flex: 1;
  border: none;
  background: transparent;
  cursor: pointer;
  font-size: 8px;
  line-height: 1;
  padding: 0;
  color: #666;
  pointer-events: all;
  display: flex;
  align-items: center;
  justify-content: center;
}

.time-btn-up:hover,
.time-btn-down:hover {
  color: #000;
  background-color: rgba(0, 0, 0, 0.05);
}

.time-separator {
  font-weight: bold;
  color: #666;
}

.time-am-pm {
  padding: 11px 2px;
  border: 1px solid #d0d0d0;
  border-radius: 4px;
  box-sizing: border-box;
  background-color: white;
  width: 70px !important;
  min-width: 70px;
  max-width: 70px;
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
