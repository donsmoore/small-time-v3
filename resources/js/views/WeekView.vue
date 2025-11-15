<template>
  <div class="week-view">
    <div v-if="loading">Loading...</div>
    <div v-else-if="error" class="error-message">{{ error }}</div>
    <div v-else-if="weekData">
      <div class="week-header">
        <div class="week-navigation">
          <div class="week-nav">
            <button
              id="print-week-btn"
              name="print-week-btn"
              v-if="userId && weekData"
              @click="goToPrint"
              class="print-button"
            >
            Print Week
            </button>
            <div class="week-nav-controls">
              <button
                id="first-week-btn"
                name="first-week-btn"
                class="pagination-arrow"
                @click="goToFirstWeek"
                :disabled="loading || isAtEarliestWeek"
                title="First Week"
              >
                «
              </button>
              <button
                id="prev-week-btn"
                name="prev-week-btn"
                class="pagination-arrow"
                @click="goPrevWeek"
                :disabled="loading || isAtEarliestWeek"
                title="Previous Week"
              >
                ‹
              </button>
              <span class="week-range">
                {{ formatDate(weekData.cursorWeekStartDateTime) }} - Through - {{ formatDate(weekData.cursorWeekEndDateTime) }}
              </span>
              <button
                id="next-week-btn"
                name="next-week-btn"
                class="pagination-arrow"
                @click="goNextWeek"
                :disabled="loading || isAtLatestWeek"
                title="Next Week"
              >
                ›
              </button>
              <button
                id="last-week-btn"
                name="last-week-btn"
                class="pagination-arrow"
                @click="goToLastWeek"
                :disabled="loading || isAtLatestWeek"
                title="Last Week"
              >
                »
              </button>
            </div>
          </div>
        </div>
        <div class="week-info">
          <table class="week-info-table">
            <tr>
              <td class="name-cell name-cell-name">Name:</td>
              <td class="data-cell">{{ userName }}</td>
              <td class="name-cell">Company:</td>
              <td class="data-cell">{{ groupName }}</td>
              <td class="name-cell">Week start:</td>
              <td class="data-cell">{{ weekData.clockWeekStartDOW }} - {{ weekData.clockWeekStartTime }}</td>
            </tr>
          </table>
        </div>
      </div>

      <table class="week-table">
        <thead>
          <tr>
            <th>#</th>
            <th>
              Clock In
              <button id="add-clock-in-header-btn" name="add-clock-in-header-btn" @click="addClockEvent(null, 'in')" class="header-add-btn" title="Add Clock In">+</button>
            </th>
            <th>
              Clock Out
              <button id="add-clock-out-header-btn" name="add-clock-out-header-btn" @click="addClockEvent(null, 'out')" class="header-add-btn" title="Add Clock Out">+</button>
            </th>
            <th>Time Worked</th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="processedEvents.length === 0">
            <td colspan="4" class="no-records">No timeclock records found for this week</td>
          </tr>
          <tr v-for="(event, index) in processedEvents" :key="index">
            <td>{{ index + 1 }}</td>
            <td :class="[
              event.inTime === '---' ? 'missing' : '',
              event.inId === -1 ? 'virtual-clock-in' : '',
              event.inTime === '????-??-?? ??:??:??' ? 'missing-time' : ''
            ]">
              <span class="time-value">{{ event.inTime }}</span>
              <span v-if="event.inId && event.inId > 0" class="cell-actions">
                <button :id="`edit-clock-in-btn-${event.inId}`" :name="`edit-clock-in-btn-${event.inId}`" @click="editClockEvent(event.inId, 'in', event.inTime)" class="action-btn edit-btn" title="Edit">✎</button>
                <button :id="`delete-clock-in-btn-${event.inId}`" :name="`delete-clock-in-btn-${event.inId}`" @click="deleteClockEvent(event.inId)" class="action-btn delete-btn" title="Delete">✕</button>
              </span>
              <span v-if="event.inTime === '????-??-?? ??:??:??'" class="cell-actions">
                <button :id="`add-clock-in-cell-btn-${index}`" :name="`add-clock-in-cell-btn-${index}`" @click="addClockEvent(event, 'in')" class="action-btn add-btn" title="Add Clock In">+</button>
              </span>
            </td>
            <td :class="[
              event.outTime === '---' ? 'missing' : '',
              event.outId === -2 ? 'virtual-clock-out' : '',
              event.outTime === '????-??-?? ??:??:??' && event.outId !== -2 ? 'missing-time' : ''
            ]">
              <span class="time-value">{{ event.outTime }}</span>
              <span v-if="event.outId && event.outId > 0" class="cell-actions">
                <button :id="`edit-clock-out-btn-${event.outId}`" :name="`edit-clock-out-btn-${event.outId}`" @click="editClockEvent(event.outId, 'out', event.outTime)" class="action-btn edit-btn" title="Edit">✎</button>
                <button :id="`delete-clock-out-btn-${event.outId}`" :name="`delete-clock-out-btn-${event.outId}`" @click="deleteClockEvent(event.outId)" class="action-btn delete-btn" title="Delete">✕</button>
              </span>
              <span v-if="event.outTime === '????-??-?? ??:??:??'" class="cell-actions">
                <button :id="`add-clock-out-cell-btn-${index}`" :name="`add-clock-out-cell-btn-${index}`" @click="addClockEvent(event, 'out')" class="action-btn add-btn" title="Add Clock Out">+</button>
              </span>
            </td>
            <td style="text-align: right;">{{ event.timeWorked }}</td>
          </tr>
          <tr v-if="processedEvents.length > 0 && totalTime" class="total-row">
            <td colspan="3" style="text-align: right; padding-right: 10px;"><strong>Total Week Time:</strong></td>
            <td style="text-align: right;"><strong>{{ totalTime }}</strong></td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Add Clock Event Modal -->
    <div v-if="showAddModal" class="modal-overlay" @mousedown="handleOverlayMouseDown($event, 'add')">
      <div ref="addModalContent" class="modal-content" :style="addModalStyle" @click.stop>
        <div class="modal-title-box" @mousedown="startDrag($event, 'add')" style="cursor: move;">
          <h3>Add {{ formData.type === 'in' ? 'Clock In' : 'Clock Out' }}</h3>
        </div>
        <form @submit.prevent="saveAddEvent">
          <div class="form-group">
            <label for="add-event-date">Date:</label>
            <input 
              id="add-event-date"
              name="add-event-date"
              ref="eventDateInput"
              v-model="formData.eventDate" 
              type="date"
              required 
            />
          </div>
          <div class="form-group">
            <label for="add-event-hours">Time:</label>
            <div class="time-widget-wrapper">
              <div class="time-input-group">
                <div class="time-inputs">
                  <div class="time-input-wrapper">
                    <input 
                      id="add-event-hours"
                      name="add-event-hours"
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
                      id="add-event-minutes"
                      name="add-event-minutes"
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
                      id="add-event-seconds"
                      name="add-event-seconds"
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
                  <select id="add-event-ampm" name="add-event-ampm" v-model="timeAMPM" class="time-am-pm">
                    <option value="AM">AM</option>
                    <option value="PM">PM</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="form-actions">
            <button id="save-add-event-btn" name="save-add-event-btn" type="submit" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
            <button id="cancel-add-event-btn" name="cancel-add-event-btn" type="button" @click="closeAddModal">Cancel</button>
          </div>
          <div v-if="addError" class="error-box">
            {{ addError }}
          </div>
        </form>
      </div>
    </div>

    <!-- Edit Clock Event Modal -->
    <div v-if="editingEvent" class="modal-overlay" @mousedown="handleOverlayMouseDown($event, 'edit')">
      <div ref="editModalContent" class="modal-content" :style="editModalStyle" @click.stop>
        <div class="modal-title-box" @mousedown="startDrag($event, 'edit')" style="cursor: move;">
          <h3>Edit {{ editingEvent.type === 'in' ? 'Clock In' : 'Clock Out' }}</h3>
        </div>
        <form @submit.prevent="saveEditEvent">
          <div class="form-group">
            <label for="edit-event-date">Date:</label>
            <input 
              id="edit-event-date"
              name="edit-event-date"
              ref="eventDateInput"
              v-model="formData.eventDate" 
              type="date"
              required 
            />
          </div>
          <div class="form-group">
            <label for="edit-event-hours">Time:</label>
            <div class="time-widget-wrapper">
              <div class="time-input-group">
                <div class="time-inputs">
                  <div class="time-input-wrapper">
                    <input 
                      id="edit-event-hours"
                      name="edit-event-hours"
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
                      id="edit-event-minutes"
                      name="edit-event-minutes"
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
                      id="edit-event-seconds"
                      name="edit-event-seconds"
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
                  <select id="edit-event-ampm" name="edit-event-ampm" v-model="timeAMPM" class="time-am-pm">
                    <option value="AM">AM</option>
                    <option value="PM">PM</option>
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="form-actions">
            <button id="save-edit-event-btn" name="save-edit-event-btn" type="submit" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
            <button id="cancel-edit-event-btn" name="cancel-edit-event-btn" type="button" @click="closeEditModal">Cancel</button>
          </div>
          <div v-if="editError" class="error-box">
            {{ editError }}
          </div>
        </form>
      </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div v-if="deletingEvent" class="modal-overlay" @mousedown="handleOverlayMouseDown($event, 'delete')">
      <div ref="deleteModalContent" class="modal-content" :style="deleteModalStyle" @click.stop>
        <div class="modal-title-box" @mousedown="startDrag($event, 'delete')" style="cursor: move;">
          <h3>Delete Clock Event</h3>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to delete this {{ deletingEvent.type === 'IN' ? 'Clock In' : 'Clock Out' }} event?</p>
          <p><strong>Time:</strong> {{ deletingEvent.time }}</p>
          <p class="warning-text">This action cannot be undone.</p>
        </div>
        <div class="form-actions">
          <button id="confirm-delete-event-btn" name="confirm-delete-event-btn" type="button" @click="confirmDelete" class="delete-confirm-btn" :disabled="saving">{{ saving ? 'Deleting...' : 'Delete' }}</button>
          <button id="cancel-delete-event-btn" name="cancel-delete-event-btn" type="button" @click="closeDeleteModal">Cancel</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, watch, onUnmounted, nextTick } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import api from '../api/index.js'

export default {
  name: 'WeekView',
  setup() {
    const route = useRoute()
    const router = useRouter()
    const userId = ref(route.params.userId ? parseInt(route.params.userId) : null)
    const weekData = ref(null)
    const loading = ref(false)
    const error = ref('')
    const groupName = ref('')
    const userName = ref('')
    const showAddModal = ref(false)
    const editingEvent = ref(null)
    const deletingEvent = ref(null)
    const saving = ref(false)
    const addError = ref('')
    const editError = ref('')
    const formData = ref({
      eventDate: '',
      eventTime: '',
      type: 'in' // 'in' or 'out'
    })
    const eventTimeInput = ref(null)
    const eventDateInput = ref(null)
    const timeHours = ref(12)
    const timeMinutes = ref(0)
    const timeSeconds = ref(0)
    const timeAMPM = ref('AM')
    
    // Track raw input values for display (to prevent immediate formatting)
    const timeHoursDisplay = ref('12')
    const timeMinutesDisplay = ref('00')
    const timeSecondsDisplay = ref('00')
    
    // Drag functionality
    const addModalContent = ref(null)
    const editModalContent = ref(null)
    const deleteModalContent = ref(null)
    const dragging = ref({ active: false, type: null, startX: 0, startY: 0, initialOffsetX: 0, initialOffsetY: 0 })
    const addModalStyle = ref({ transform: 'translate(0, 0)' })
    const editModalStyle = ref({ transform: 'translate(0, 0)' })
    const deleteModalStyle = ref({ transform: 'translate(0, 0)' })
    
    // Helper function to parse translate values from transform string
    const parseTransform = (transformString) => {
      const match = transformString.match(/translate\(([-\d.]+)px,\s*([-\d.]+)px\)/)
      if (match) {
        return {
          x: parseFloat(match[1]) || 0,
          y: parseFloat(match[2]) || 0
        }
      }
      return { x: 0, y: 0 }
    }
    
    const startDrag = (event, modalType) => {
      event.preventDefault()
      
      // Get current transform values to accumulate offset
      let currentOffsetX = 0
      let currentOffsetY = 0
      
      if (modalType === 'add') {
        const currentTransform = parseTransform(addModalStyle.value.transform)
        currentOffsetX = currentTransform.x
        currentOffsetY = currentTransform.y
      } else if (modalType === 'edit') {
        const currentTransform = parseTransform(editModalStyle.value.transform)
        currentOffsetX = currentTransform.x
        currentOffsetY = currentTransform.y
      } else if (modalType === 'delete') {
        const currentTransform = parseTransform(deleteModalStyle.value.transform)
        currentOffsetX = currentTransform.x
        currentOffsetY = currentTransform.y
      }
      
      dragging.value = {
        active: true,
        type: modalType,
        startX: event.clientX,
        startY: event.clientY,
        initialOffsetX: currentOffsetX,
        initialOffsetY: currentOffsetY
      }
      document.addEventListener('mousemove', handleDrag)
      document.addEventListener('mouseup', stopDrag)
    }
    
    const handleDrag = (event) => {
      if (!dragging.value.active) return
      
      // Calculate new offset from mouse movement and add to initial offset
      const deltaX = event.clientX - dragging.value.startX
      const deltaY = event.clientY - dragging.value.startY
      
      const offsetX = dragging.value.initialOffsetX + deltaX
      const offsetY = dragging.value.initialOffsetY + deltaY
      
      const style = { transform: `translate(${offsetX}px, ${offsetY}px)` }
      
      if (dragging.value.type === 'add') {
        addModalStyle.value = style
      } else if (dragging.value.type === 'edit') {
        editModalStyle.value = style
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
            if (modalType === 'add') {
              closeAddModal()
            } else if (modalType === 'edit') {
              closeEditModal()
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
      addModalStyle.value = { transform: 'translate(0, 0)' }
      editModalStyle.value = { transform: 'translate(0, 0)' }
      deleteModalStyle.value = { transform: 'translate(0, 0)' }
    }
    
    onUnmounted(() => {
      document.removeEventListener('mousemove', handleDrag)
      document.removeEventListener('mouseup', stopDrag)
    })

    const formatDate = (dateString) => {
      if (!dateString) return ''
      return dateString.substring(0, 10)
    }

    const calculateWeekMidpoint = (weekStartDateTime) => {
      if (!weekStartDateTime) return null
      // Calculate midpoint: weekStart + 3.5 days (halfway through the week)
      const weekStart = new Date(weekStartDateTime.replace(' ', 'T'))
      const midpoint = new Date(weekStart.getTime() + (3.5 * 24 * 60 * 60 * 1000))
      return midpoint.toISOString().slice(0, 19).replace('T', ' ')
    }

    // Helper function to check if time difference is within 24 hours
    const isWithin24Hours = (dateTime1, dateTime2) => {
      if (!dateTime1 || !dateTime2) return false
      try {
        const date1 = new Date(dateTime1)
        const date2 = new Date(dateTime2)
        const diffMs = Math.abs(date2 - date1)
        const diffHours = diffMs / (1000 * 60 * 60)
        return diffHours <= 24
      } catch (e) {
        return false
      }
    }

    const generateWeekTimeData = (events, weekData) => {
      if (!events || events.length === 0) return []
      
      const weekTimeData = []
      let currentPair = { inId: 0, whenIn: '', outId: 0, whenOut: '' }
      let row = 0
      
      // Filter out events with empty/null inOrOut values
      const validEvents = events.filter(e => e.inOrOut && (e.inOrOut === 'IN' || e.inOrOut === 'OUT'))
      
      // Check for beginning of week transition
      // Requirements: First valid event in week is OUT AND last event before week start is IN
      const eventBeforeWeekStart = weekData?.eventBeforeWeekStart
      const firstValidEvent = validEvents.length > 0 ? validEvents[0] : null
      const isBeginningTransition = firstValidEvent && 
                                    firstValidEvent.inOrOut === 'OUT' && // First valid event in week must be OUT
                                    eventBeforeWeekStart && 
                                    eventBeforeWeekStart.inOrOut === 'IN' && // Last event before week start must be IN
                                    isWithin24Hours(eventBeforeWeekStart.eventTime, firstValidEvent.eventTime)
      
      // Check for end of week transition
      // Requirements: Last valid event in week is IN AND first event after week end is OUT
      const eventAfterWeekEnd = weekData?.eventAfterWeekEnd
      const lastValidEvent = validEvents.length > 0 ? validEvents[validEvents.length - 1] : null
      const isEndTransition = lastValidEvent && 
                              lastValidEvent.inOrOut === 'IN' && // Last valid event in week must be IN
                              eventAfterWeekEnd && 
                              eventAfterWeekEnd.inOrOut === 'OUT' && // First event after week end must be OUT
                              isWithin24Hours(lastValidEvent.eventTime, eventAfterWeekEnd.eventTime)
      
      events.forEach((event, index) => {
        if (event.inOrOut === 'IN') {
          if (currentPair.whenIn) {
            // Start new pair if we already have an IN
            row++
            weekTimeData.push({
              row: row,
              inId: currentPair.inId,
              whenIn: currentPair.inId === 0 ? '????-??-?? ??:??:??' : currentPair.whenIn,
              outId: 0,
              whenOut: '????-??-?? ??:??:??',
              timeHrsMinSec: '',
              timeHrs: 0,
              timeMin: 0,
              timeSec: 0,
              totSec: 0
            })
            currentPair = { inId: 0, whenIn: '', outId: 0, whenOut: '' }
          }
          currentPair.whenIn = formatDateTime(event.eventTime)
          currentPair.inId = event.id
          
          // Note: Don't mark real event IDs as -2 for end transitions
          // The -2 should only be used for virtual/transitional pairs, not real events
          // Real events should keep their original ID
        } else if (event.inOrOut === 'OUT') {
          // Check if this is the first valid OUT event and it's a beginning transition
          // Find the index of this event in the validEvents array
          const validOutIndex = validEvents.findIndex(e => e.id === event.id && e.inOrOut === 'OUT')
          if (validOutIndex === 0 && isBeginningTransition && !currentPair.whenIn) {
            currentPair.inId = -1 // Beginning of week transition
            // Use the week start date/time as the virtual clock in
            currentPair.whenIn = formatDateTime(weekData?.cursorWeekStartDateTime || '')
          }
          
          // If we don't have an IN yet, this shouldn't happen normally, but handle it
          if (!currentPair.whenIn) {
            currentPair.whenIn = '????-??-?? ??:??:??' // Missing IN
            currentPair.inId = 0
          }
          
          currentPair.whenOut = formatDateTime(event.eventTime)
          currentPair.outId = event.id
          
          // Check if the time difference exceeds 24 hours
          if (exceeds24Hours(currentPair.whenIn, currentPair.whenOut)) {
            // Split into two entries: IN with missing OUT, then missing IN with OUT
            // First entry: Clock In with missing Clock Out
            row++
            weekTimeData.push({
              row: row,
              inId: currentPair.inId,
              whenIn: currentPair.inId === 0 ? '????-??-?? ??:??:??' : currentPair.whenIn,
              outId: 0,
              whenOut: '????-??-?? ??:??:??',
              timeHrsMinSec: '',
              timeHrs: 0,
              timeMin: 0,
              timeSec: 0,
              totSec: 0
            })
            
            // Second entry: Missing Clock In with Clock Out
            row++
            weekTimeData.push({
              row: row,
              inId: 0,
              whenIn: '????-??-?? ??:??:??',
              outId: currentPair.outId,
              whenOut: currentPair.whenOut,
              timeHrsMinSec: '',
              timeHrs: 0,
              timeMin: 0,
              timeSec: 0,
              totSec: 0
            })
          } else {
            // Normal pair within 24 hours - Calculate time worked
            const timeData = calculateTimeData(currentPair.whenIn, currentPair.whenOut)
            row++
            weekTimeData.push({
              row: row,
              inId: currentPair.inId,
              whenIn: currentPair.inId === 0 ? '????-??-?? ??:??:??' : currentPair.whenIn,
              outId: currentPair.outId,
              whenOut: currentPair.outId === 0 ? '????-??-?? ??:??:??' : currentPair.whenOut,
              timeHrsMinSec: timeData.timeHrsMinSec,
              timeHrs: timeData.timeHrs,
              timeMin: timeData.timeMin,
              timeSec: timeData.timeSec,
              totSec: timeData.totSec
            })
          }
          
          currentPair = { inId: 0, whenIn: '', outId: 0, whenOut: '' }
        }
      })
      
      // Handle last IN without OUT
      if (currentPair.whenIn) {
        // Check if this is an end transition (last event is IN and there's an OUT after week end)
        // If isEndTransition is true, this should be treated as a transition regardless of inId value
        if (isEndTransition) {
          // Use the week end date/time as the virtual clock out
          const virtualClockOut = formatDateTime(weekData?.cursorWeekEndDateTime)
          if (virtualClockOut && virtualClockOut !== '---') {
            currentPair.whenOut = virtualClockOut
            currentPair.outId = -2 // Mark virtual clock out as end transition
            // Keep the real event ID for inId - don't mark real events as -2
            // Only use -2 for virtual/transitional events, not real clock in events
            // Calculate time worked using the virtual clock out
            const timeData = calculateTimeData(currentPair.whenIn, currentPair.whenOut)
            row++
            weekTimeData.push({
              row: row,
              inId: currentPair.inId,
              whenIn: currentPair.whenIn,
              outId: currentPair.outId,
              whenOut: currentPair.whenOut,
              timeHrsMinSec: timeData.timeHrsMinSec,
              timeHrs: timeData.timeHrs,
              timeMin: timeData.timeMin,
              timeSec: timeData.timeSec,
              totSec: timeData.totSec
            })
          } else {
            // Fallback: try to use week start + 7 days if week end is not available
            const fallbackWeekEnd = weekData?.cursorWeekStartDateTime ? 
              formatDateTime(new Date(new Date(weekData.cursorWeekStartDateTime).getTime() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 19).replace('T', ' ')) :
              '---'
            row++
            weekTimeData.push({
              row: row,
              inId: currentPair.inId || 0, // Keep real event ID, don't override with -2
              whenIn: currentPair.whenIn,
              outId: -2,
              whenOut: fallbackWeekEnd,
              timeHrsMinSec: '',
              timeHrs: 0,
              timeMin: 0,
              timeSec: 0,
              totSec: 0
            })
          }
        } else {
          row++
          weekTimeData.push({
            row: row,
            inId: currentPair.inId, // Will be -2 if end transition, otherwise regular id
            whenIn: currentPair.inId === 0 ? '????-??-?? ??:??:??' : currentPair.whenIn,
            outId: 0,
            whenOut: '????-??-?? ??:??:??',
            timeHrsMinSec: '',
            timeHrs: 0,
            timeMin: 0,
            timeSec: 0,
            totSec: 0
          })
        }
      }
      
      return weekTimeData
    }

    const processEvents = (events, weekData) => {
      if (!events || events.length === 0) return []
      
      const pairs = []
      let currentPair = { inTime: '', outTime: '', inId: null, outId: null }
      
      // Filter out events with empty/null inOrOut values
      const validEvents = events.filter(e => e.inOrOut && (e.inOrOut === 'IN' || e.inOrOut === 'OUT'))
      
      // Check for beginning of week transition
      // Requirements: First valid event in week is OUT AND last event before week start is IN
      const eventBeforeWeekStart = weekData?.eventBeforeWeekStart
      const firstValidEvent = validEvents.length > 0 ? validEvents[0] : null
      const isBeginningTransition = firstValidEvent && 
                                    firstValidEvent.inOrOut === 'OUT' && // First valid event in week must be OUT
                                    eventBeforeWeekStart && 
                                    eventBeforeWeekStart.inOrOut === 'IN' && // Last event before week start must be IN
                                    isWithin24Hours(eventBeforeWeekStart.eventTime, firstValidEvent.eventTime)
      
      // Check for end of week transition
      // Requirements: Last valid event in week is IN AND first event after week end is OUT
      const eventAfterWeekEnd = weekData?.eventAfterWeekEnd
      const lastValidEvent = validEvents.length > 0 ? validEvents[validEvents.length - 1] : null
      const isEndTransition = lastValidEvent && 
                              lastValidEvent.inOrOut === 'IN' && // Last valid event in week must be IN
                              eventAfterWeekEnd && 
                              eventAfterWeekEnd.inOrOut === 'OUT' && // First event after week end must be OUT
                              isWithin24Hours(lastValidEvent.eventTime, eventAfterWeekEnd.eventTime)
      
      events.forEach((event, index) => {
        if (event.inOrOut === 'IN') {
          if (currentPair.inTime) {
            // Start new pair if we already have an IN
            pairs.push({ 
              ...currentPair, 
              inTime: currentPair.inId === 0 ? '????-??-?? ??:??:??' : currentPair.inTime,
              outTime: (!currentPair.outTime || currentPair.outId === 0 || currentPair.outId === null) ? '????-??-?? ??:??:??' : currentPair.outTime, 
              timeWorked: '---' 
            })
            currentPair = { inTime: '', outTime: '', inId: null, outId: null }
          }
          currentPair.inTime = formatDateTime(event.eventTime)
          currentPair.inId = event.id
          
          // Note: Don't mark real event IDs as -2 for end transitions
          // The -2 should only be used for virtual/transitional pairs, not real events
          // Real events (like event.id = 153) should keep their original ID so buttons show
        } else if (event.inOrOut === 'OUT') {
          // Check if this is the first valid OUT event and it's a beginning transition
          // Find the index of this event in the validEvents array
          const validOutIndex = validEvents.findIndex(e => e.id === event.id && e.inOrOut === 'OUT')
          if (validOutIndex === 0 && isBeginningTransition && !currentPair.inTime) {
            currentPair.inId = -1 // Beginning of week transition (virtual clock in)
            // Use the week start date/time as the virtual clock in
            currentPair.inTime = formatDateTime(weekData?.cursorWeekStartDateTime || '')
          }
          
          // If we don't have an IN yet, this shouldn't happen normally, but handle it
          if (!currentPair.inTime) {
            currentPair.inTime = '????-??-?? ??:??:??' // Missing IN
            currentPair.inId = 0
          }
          
          currentPair.outTime = formatDateTime(event.eventTime)
          currentPair.outId = event.id
          
          // Check if the time difference exceeds 24 hours
          if (exceeds24Hours(currentPair.inTime, currentPair.outTime)) {
            // Split into two entries: IN with missing OUT, then missing IN with OUT
            // First entry: Clock In with missing Clock Out
            pairs.push({
              inTime: currentPair.inId === 0 ? '????-??-?? ??:??:??' : currentPair.inTime,
              inId: currentPair.inId,
              outTime: '????-??-?? ??:??:??',
              outId: 0,
              timeWorked: '---'
            })
            
            // Second entry: Missing Clock In with Clock Out
            pairs.push({
              inTime: '????-??-?? ??:??:??',
              inId: 0,
              outTime: currentPair.outTime,
              outId: currentPair.outId,
              timeWorked: '---'
            })
          } else {
            // Normal pair within 24 hours
            pairs.push({ 
              ...currentPair, 
              inTime: currentPair.inId === 0 ? '????-??-?? ??:??:??' : currentPair.inTime,
              outTime: currentPair.outId === 0 ? '????-??-?? ??:??:??' : currentPair.outTime,
              timeWorked: calculateTimeWorked(currentPair.inTime, currentPair.outTime) 
            })
          }
          
          currentPair = { inTime: '', outTime: '', inId: null, outId: null }
        }
      })
      
      // Handle last IN without OUT
      if (currentPair.inTime) {
        // Check if this is an end transition (last event is IN and there's an OUT after week end)
        // If isEndTransition is true, this should be treated as a transition regardless of inId value
        if (isEndTransition) {
          // Use the week end date/time as the virtual clock out
          const virtualClockOut = formatDateTime(weekData?.cursorWeekEndDateTime)
          if (virtualClockOut && virtualClockOut !== '---') {
            currentPair.outTime = virtualClockOut
            currentPair.outId = -2 // Mark virtual clock out as end transition
            // Keep the real event ID for inId - don't mark real events as -2
            // Only use -2 for virtual/transitional events, not real clock in events
            // Calculate time worked using the virtual clock out
            const timeData = calculateTimeData(currentPair.inTime, currentPair.outTime)
            pairs.push({
              ...currentPair,
              timeWorked: timeData.timeHrsMinSec || '---'
            })
          } else {
            // Fallback: try to use week start + 7 days if week end is not available
            const fallbackWeekEnd = weekData?.cursorWeekStartDateTime ? 
              formatDateTime(new Date(new Date(weekData.cursorWeekStartDateTime).getTime() + 7 * 24 * 60 * 60 * 1000).toISOString().slice(0, 19).replace('T', ' ')) :
              '---'
            pairs.push({
              ...currentPair,
              inId: currentPair.inId || 0, // Keep real event ID, don't override with -2
              outTime: fallbackWeekEnd,
              outId: -2, // Virtual clock out is -2
              timeWorked: fallbackWeekEnd !== '---' ? calculateTimeWorked(currentPair.inTime, fallbackWeekEnd) : '---'
            })
          }
        } else {
          // Handle last IN without OUT (not an end transition)
          // Ensure inId is explicitly set - it should be from event.id if we have inTime
          // Explicitly include inId in the object to ensure it's always present
          pairs.push({ 
            ...currentPair,
            inId: currentPair.inId, // Explicitly include inId - should be set from event.id at line 800
            inTime: currentPair.inId === 0 ? '????-??-?? ??:??:??' : currentPair.inTime,
            outTime: '????-??-?? ??:??:??',
            outId: 0,
            timeWorked: '---' 
          })
        }
      }
      
      return pairs
    }

    const formatDateTime = (dateTime) => {
      if (!dateTime) return '---'
      try {
        const date = new Date(dateTime)
        // Check if date is valid
        if (isNaN(date.getTime())) {
          return '---'
        }
        // Format as abbreviated month and day with ordinal (e.g., "Nov 10th") - HH:MM:SS AM/PM
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        const month = monthNames[date.getMonth()]
        const day = date.getDate()
        
        // Add ordinal suffix
        const getOrdinalSuffix = (n) => {
          const j = n % 10
          const k = n % 100
          if (j === 1 && k !== 11) return 'st'
          if (j === 2 && k !== 12) return 'nd'
          if (j === 3 && k !== 13) return 'rd'
          return 'th'
        }
        const dayWithOrdinal = `${day}${getOrdinalSuffix(day)}`
        
        let hours24 = date.getHours()
        const minutes = String(date.getMinutes()).padStart(2, '0')
        const seconds = String(date.getSeconds()).padStart(2, '0')
        
        // Convert to 12-hour format
        let hours12 = hours24
        let ampm = 'AM'
        if (hours24 === 0) {
          hours12 = 12
          ampm = 'AM'
        } else if (hours24 === 12) {
          hours12 = 12
          ampm = 'PM'
        } else if (hours24 > 12) {
          hours12 = hours24 - 12
          ampm = 'PM'
        } else {
          hours12 = hours24
          ampm = 'AM'
        }
        
        return `${month} ${dayWithOrdinal} - ${String(hours12).padStart(2, '0')}:${minutes}:${seconds} ${ampm}`
      } catch (e) {
        console.error('Error formatting date:', dateTime, e)
        return '---'
      }
    }

    // Helper function to parse displayed date/time format (Nov 10th - HH:MM:SS AM/PM or MM-DD - HH:MM:SS AM/PM)
    const parseDisplayedDateTime = (dateTimeStr) => {
      if (!dateTimeStr || dateTimeStr === '---' || dateTimeStr === '????-??-?? ??:??:??') {
        return null
      }
      
      // Try new format: "Nov 10th - HH:MM:SS AM/PM"
      const newFormatMatch = dateTimeStr.match(/([A-Za-z]{3})\s+(\d{1,2})(st|nd|rd|th)\s*-\s*(\d{1,2}):(\d{2}):(\d{2})\s+(AM|PM)/)
      if (newFormatMatch) {
        const [, monthAbbr, day, , hour, minute, second, ampm] = newFormatMatch
        const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
        const monthIndex = monthNames.findIndex(m => m.toLowerCase() === monthAbbr.toLowerCase())
        if (monthIndex !== -1) {
          let hour24 = parseInt(hour)
          if (ampm === 'PM' && hour24 !== 12) hour24 += 12
          if (ampm === 'AM' && hour24 === 12) hour24 = 0
          const currentYear = new Date().getFullYear()
          return new Date(currentYear, monthIndex, parseInt(day), hour24, parseInt(minute), parseInt(second))
        }
      }
      
      // Try old format: "MM-DD - HH:MM:SS AM/PM" (for backward compatibility)
      const oldFormatMatch = dateTimeStr.match(/(\d{2})-(\d{2})\s*-\s*(\d{1,2}):(\d{2}):(\d{2})\s+(AM|PM)/)
      if (oldFormatMatch) {
        const [, month, day, hour, minute, second, ampm] = oldFormatMatch
        let hour24 = parseInt(hour)
        if (ampm === 'PM' && hour24 !== 12) hour24 += 12
        if (ampm === 'AM' && hour24 === 12) hour24 = 0
        const currentYear = new Date().getFullYear()
        return new Date(currentYear, parseInt(month) - 1, parseInt(day), hour24, parseInt(minute), parseInt(second))
      }
      
      // Fallback: try to parse as standard date format
      try {
        return new Date(dateTimeStr.replace(' ', 'T'))
      } catch (e) {
        return null
      }
    }

    const exceeds24Hours = (inTime, outTime) => {
      if (!inTime || !outTime || inTime === '---' || outTime === '---' || 
          inTime === '????-??-?? ??:??:??' || outTime === '????-??-?? ??:??:??') {
        return false
      }
      
      try {
        const inDate = parseDisplayedDateTime(inTime)
        const outDate = parseDisplayedDateTime(outTime)
        
        if (!inDate || !outDate) return false
        
        const diffMs = outDate - inDate
        const diffSec = Math.floor(diffMs / 1000)
        
        // Check if difference exceeds 24 hours (86400 seconds)
        return diffSec > 86400
      } catch (e) {
        return false
      }
    }

    const calculateTimeWorked = (inTime, outTime) => {
      if (inTime === '---' || outTime === '---' || inTime === '????-??-?? ??:??:??' || outTime === '????-??-?? ??:??:??') return '---'
      
      try {
        const inDate = parseDisplayedDateTime(inTime)
        const outDate = parseDisplayedDateTime(outTime)
        
        if (!inDate || !outDate) return '---'
        
        const diffMs = outDate - inDate
        
        if (isNaN(diffMs) || diffMs < 0) return '---'
        
        const diffSec = Math.floor(diffMs / 1000)
        const hours = Math.floor(diffSec / 3600)
        const minutes = Math.floor((diffSec % 3600) / 60)
        const seconds = diffSec % 60
        
        return `${String(hours).padStart(2, '0')}h : ${String(minutes).padStart(2, '0')}m : ${String(seconds).padStart(2, '0')}s`
      } catch (e) {
        return '---'
      }
    }

    const calculateTimeData = (inTime, outTime) => {
      if (!inTime || !outTime || inTime === '---' || outTime === '---' || inTime === '????-??-?? ??:??:??' || outTime === '????-??-?? ??:??:??') {
        return {
          timeHrsMinSec: '',
          timeHrs: 0,
          timeMin: 0,
          timeSec: 0,
          totSec: 0
        }
      }
      
      try {
        const inDate = parseDisplayedDateTime(inTime)
        const outDate = parseDisplayedDateTime(outTime)
        
        if (!inDate || !outDate) {
          return {
            timeHrsMinSec: '',
            timeHrs: 0,
            timeMin: 0,
            timeSec: 0,
            totSec: 0
          }
        }
        
        const diffMs = outDate - inDate
        
        if (isNaN(diffMs) || diffMs < 0) {
          return {
            timeHrsMinSec: '',
            timeHrs: 0,
            timeMin: 0,
            timeSec: 0,
            totSec: 0
          }
        }
        
        const totSec = Math.floor(diffMs / 1000)
        const timeHrs = Math.floor(totSec / 3600)
        const timeMin = Math.floor((totSec % 3600) / 60)
        const timeSec = totSec % 60
        
        const timeHrsMinSec = `${String(timeHrs).padStart(2, '0')}h : ${String(timeMin).padStart(2, '0')}m : ${String(timeSec).padStart(2, '0')}s`
        
        return {
          timeHrsMinSec,
          timeHrs,
          timeMin,
          timeSec,
          totSec
        }
      } catch (e) {
        return {
          timeHrsMinSec: '',
          timeHrs: 0,
          timeMin: 0,
          timeSec: 0,
          totSec: 0
        }
      }
    }

    const processedEvents = computed(() => {
      if (!weekData.value || !weekData.value.events) return []
      return processEvents(weekData.value.events, weekData.value)
    })

    const weekTimeData = computed(() => {
      if (!weekData.value || !weekData.value.events) return []
      return generateWeekTimeData(weekData.value.events, weekData.value)
    })

    const totalTime = computed(() => {
      if (!processedEvents.value || processedEvents.value.length === 0) return ''
      
      let totalSeconds = 0
      processedEvents.value.forEach(event => {
        if (event.timeWorked !== '---') {
          const match = event.timeWorked.match(/(\d+)h.*?(\d+)m.*?(\d+)s/)
          if (match) {
            totalSeconds += parseInt(match[1]) * 3600 + parseInt(match[2]) * 60 + parseInt(match[3])
          }
        }
      })
      
      const hours = Math.floor(totalSeconds / 3600)
      const minutes = Math.floor((totalSeconds % 3600) / 60)
      const seconds = totalSeconds % 60
      
      return `${String(hours).padStart(2, '0')}h ${String(minutes).padStart(2, '0')}m ${String(seconds).padStart(2, '0')}s`
    })

    const loadWeekData = async (cursorDateTime = null) => {
      if (!userId.value) return
      
      loading.value = true
      error.value = ''
      
      try {
        const params = {}
        if (cursorDateTime) params.cursorDateTime = cursorDateTime
        
        const response = await api.week.get(userId.value, params)
        weekData.value = response.data.data
        
        // Set group name from response
        groupName.value = weekData.value.groupName || ''
        
        // Load user name
        try {
          const userResponse = await api.users.getAll()
          const users = userResponse.data.data || []
          const user = users.find(u => u.id === userId.value)
          userName.value = user ? user.name : ''
        } catch (e) {
          console.error('Error loading user:', e)
        }
        
        // Update route query to preserve cursorDateTime (use midpoint of week)
        if (weekData.value.cursorWeekStartDateTime) {
          const midpointDateTime = calculateWeekMidpoint(weekData.value.cursorWeekStartDateTime)
          if (midpointDateTime) {
            router.replace({
              path: `/week/${userId.value}`,
              query: { cursorDateTime: midpointDateTime }
            })
          }
        }
      } catch (err) {
        error.value = err.response?.data?.error || err.message || 'Error loading week data'
        console.error('Error loading week data:', err)
      } finally {
        loading.value = false
      }
    }

    const goPrevWeek = () => {
      if (weekData.value) {
        const prevDate = new Date(weekData.value.cursorWeekStartDateTime)
        prevDate.setDate(prevDate.getDate() - 7)
        const prevWeekStart = prevDate.toISOString().slice(0, 19).replace('T', ' ')
        // Calculate midpoint of the previous week
        const cursorDateTime = calculateWeekMidpoint(prevWeekStart)
        if (cursorDateTime) {
          // Update route query to preserve cursorDateTime
          router.push({
            path: `/week/${userId.value}`,
            query: { cursorDateTime }
          })
          loadWeekData(cursorDateTime)
        }
      }
    }

    const goNextWeek = () => {
      if (weekData.value) {
        const nextDate = new Date(weekData.value.cursorWeekStartDateTime)
        nextDate.setDate(nextDate.getDate() + 7)
        const nextWeekStart = nextDate.toISOString().slice(0, 19).replace('T', ' ')
        // Calculate midpoint of the next week
        const cursorDateTime = calculateWeekMidpoint(nextWeekStart)
        if (cursorDateTime) {
          // Update route query to preserve cursorDateTime
          router.push({
            path: `/week/${userId.value}`,
            query: { cursorDateTime }
          })
          loadWeekData(cursorDateTime)
        }
      }
    }

    const goToFirstWeek = () => {
      if (!weekData.value?.earliestWeekStartDateTime) return
      const cursorDateTime = calculateWeekMidpoint(weekData.value.earliestWeekStartDateTime)
      if (cursorDateTime) {
        router.push({
          path: `/week/${userId.value}`,
          query: { cursorDateTime }
        })
        loadWeekData(cursorDateTime)
      }
    }

    const goToLastWeek = () => {
      if (!weekData.value?.latestWeekStartDateTime) return
      const cursorDateTime = calculateWeekMidpoint(weekData.value.latestWeekStartDateTime)
      if (cursorDateTime) {
        router.push({
          path: `/week/${userId.value}`,
          query: { cursorDateTime }
        })
        loadWeekData(cursorDateTime)
      }
    }

    const isAtEarliestWeek = computed(() => {
      if (!weekData.value?.cursorWeekStartDateTime || !weekData.value?.earliestWeekStartDateTime) {
        return false
      }
      return weekData.value.cursorWeekStartDateTime === weekData.value.earliestWeekStartDateTime
    })

    const isAtLatestWeek = computed(() => {
      if (!weekData.value?.cursorWeekStartDateTime || !weekData.value?.latestWeekStartDateTime) {
        return false
      }
      return weekData.value.cursorWeekStartDateTime === weekData.value.latestWeekStartDateTime
    })

    watch(() => route.params.userId, (newUserId) => {
      userId.value = newUserId ? parseInt(newUserId) : null
      if (userId.value) {
        // Use cursorDateTime from query parameter if available, otherwise use current weekData
        const cursorDateTime = route.query.cursorDateTime || 
                              (weekData.value?.cursorDateTime ? weekData.value.cursorDateTime : null)
        loadWeekData(cursorDateTime)
      }
    })

    const printUrl = computed(() => {
      if (!userId.value || !weekData.value) return '#'
      const params = new URLSearchParams({
        userId: userId.value.toString(),
        cursorDateTime: weekData.value.cursorDateTime || new Date().toISOString().slice(0, 19).replace('T', ' ')
      })
      // Include base path for subdirectory setup
      const basePath = window.location.pathname.split('/').slice(0, 3).join('/') || '/timeclock/v3'
      return `${basePath}/print?${params.toString()}`
    })

    const goToPrint = () => {
      if (!userId.value || !weekData.value) return
      const params = {
        userId: userId.value.toString(),
        cursorDateTime: weekData.value.cursorDateTime || new Date().toISOString().slice(0, 19).replace('T', ' ')
      }
      // Construct URL using current window location to ensure correct base
      const queryString = new URLSearchParams(params).toString()
      // Extract base path from current location (e.g., /timeclock/v3)
      const basePath = window.location.pathname.split('/').slice(0, 3).join('/') || '/timeclock/v3'
      const baseUrl = window.location.protocol + '//' + window.location.host
      const url = `${baseUrl}${basePath}/print?${queryString}`
      
      // Create a temporary link element and click it (works better with browser security)
      const link = document.createElement('a')
      link.href = url
      link.target = '_blank'
      link.rel = 'noopener noreferrer'
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
    }

    const formatRawData = (events) => {
      if (!events || events.length === 0) return '[]'
      return JSON.stringify(events, null, 2)
    }

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

    const parseDateTimeFromDisplayed = (displayedTime) => {
      // Parse Nov 10th - HH:MM:SS AM/PM or MM-DD - HH:MM:SS AM/PM format
      if (!displayedTime || displayedTime === '---' || displayedTime === '????-??-?? ??:??:??') {
        const now = new Date()
        formData.value.eventDate = now.toISOString().slice(0, 10)
        timeHours.value = 12
        timeMinutes.value = 0
        timeSeconds.value = 0
        timeAMPM.value = 'AM'
        timeHoursDisplay.value = '12'
        timeMinutesDisplay.value = '00'
        timeSecondsDisplay.value = '00'
        return
      }

      const dateObj = parseDisplayedDateTime(displayedTime)
      if (dateObj && !isNaN(dateObj.getTime())) {
        const year = dateObj.getFullYear()
        const month = String(dateObj.getMonth() + 1).padStart(2, '0')
        const day = String(dateObj.getDate()).padStart(2, '0')
        let hours24 = dateObj.getHours()
        const minutes = dateObj.getMinutes()
        const seconds = dateObj.getSeconds()
        
        formData.value.eventDate = `${year}-${month}-${day}`
        
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
        // Fallback to current date/time
        const now = new Date()
        formData.value.eventDate = now.toISOString().slice(0, 10)
        timeHours.value = 12
        timeMinutes.value = 0
        timeSeconds.value = 0
        timeAMPM.value = 'AM'
        timeHoursDisplay.value = '12'
        timeMinutesDisplay.value = '00'
        timeSecondsDisplay.value = '00'
      }
    }

    const parseDateTimeFrom24Hour = (dateTime24) => {
      if (!dateTime24) {
        const now = new Date()
        formData.value.eventDate = now.toISOString().slice(0, 10)
        timeHours.value = 12
        timeMinutes.value = 0
        timeSeconds.value = 0
        timeAMPM.value = 'AM'
        timeHoursDisplay.value = '12'
        timeMinutesDisplay.value = '00'
        timeSecondsDisplay.value = '00'
        return
      }

      // Parse YYYY-MM-DD HH:MM:SS format
      const [datePart, timePart] = dateTime24.split(' ')
      if (datePart) {
        formData.value.eventDate = datePart
      } else {
        const now = new Date()
        formData.value.eventDate = now.toISOString().slice(0, 10)
      }

      if (timePart) {
        const parts = timePart.split(':')
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
        }
      } else {
        timeHours.value = 12
        timeMinutes.value = 0
        timeSeconds.value = 0
        timeAMPM.value = 'AM'
        timeHoursDisplay.value = '12'
        timeMinutesDisplay.value = '00'
        timeSecondsDisplay.value = '00'
      }
    }

    const convertTo24HourFormat = () => {
      // Convert to 24-hour format
      let hours24 = timeHours.value
      if (timeAMPM.value === 'PM' && hours24 !== 12) {
        hours24 += 12
      } else if (timeAMPM.value === 'AM' && hours24 === 12) {
        hours24 = 0
      }

      // Format as YYYY-MM-DD HH:MM:SS
      const date = formData.value.eventDate || new Date().toISOString().slice(0, 10)
      const time = `${String(hours24).padStart(2, '0')}:${String(timeMinutes.value).padStart(2, '0')}:${String(timeSeconds.value).padStart(2, '0')}`
      return `${date} ${time}`
    }

    const editClockEvent = async (eventId, type, displayedTime = null) => {
      resetModalPositions()
      editError.value = '' // Clear any previous errors
      // Find the event in the current week data
      const event = weekData.value?.events?.find(e => e.id === eventId)
      
      if (event) {
        editingEvent.value = { id: eventId, type, currentTime: formatDateTime(event.eventTime) }
        
        // Wait for modal to render before parsing time
        await nextTick()
        // Add a small delay to ensure modal is fully rendered
        await new Promise(resolve => setTimeout(resolve, 50))
        
        // Priority: Use displayed time from cell if available (Nov 10th - HH:MM:SS AM/PM or MM-DD - HH:MM:SS AM/PM format)
        if (displayedTime && displayedTime !== '---' && displayedTime !== '????-??-?? ??:??:??') {
          parseDateTimeFromDisplayed(displayedTime)
        } else {
          // Fallback: Parse from event.eventTime (YYYY-MM-DD HH:MM:SS format)
          parseDateTimeFrom24Hour(event.eventTime)
        }
        
        formData.value.type = type
        setTimeout(() => {
          if (eventDateInput.value) {
            eventDateInput.value.focus()
          }
        }, 100)
      }
    }

    const deleteClockEvent = async (eventId) => {
      resetModalPositions()
      const event = weekData.value?.events?.find(e => e.id === eventId)
      if (event) {
        deletingEvent.value = { id: eventId, time: formatDateTime(event.eventTime), type: event.inOrOut }
      }
    }

    const addClockEvent = async (event, type) => {
      resetModalPositions()
      addError.value = '' // Clear any previous errors
      if (!userId.value) {
        addError.value = 'User ID is missing'
        return
      }
      
      // Determine default value based on the corresponding clock in/out
      if (event === null) {
        // Header button - use work week start date and time
        if (weekData.value?.cursorWeekStartDateTime) {
          // Parse the full datetime - this will set both date and time from week start
          parseDateTimeFrom24Hour(weekData.value.cursorWeekStartDateTime)
        } else {
          const now = new Date()
          parseDateTimeFrom24Hour(now.toISOString().slice(0, 19).replace('T', ' '))
        }
        showAddModal.value = true
        formData.value.type = type
        setTimeout(() => {
          if (eventDateInput.value) {
            eventDateInput.value.focus()
          }
        }, 100)
        return
      } else if (type === 'in') {
        // Adding Clock In - default to Clock Out time from the same row if available
        const defaultTime = event.outTime && event.outTime !== '????-??-?? ??:??:??' && event.outTime !== '---' 
          ? event.outTime 
          : null
        
        if (defaultTime) {
          // Use displayed time format (MM-DD - HH:MM:SS AM/PM)
          parseDateTimeFromDisplayed(defaultTime)
        } else {
          // Fallback to current date/time
          const now = new Date()
          parseDateTimeFrom24Hour(now.toISOString().slice(0, 19).replace('T', ' '))
        }
      } else {
        // Adding Clock Out - default to Clock In time from the same row if available
        const defaultTime = event.inTime && event.inTime !== '????-??-?? ??:??:??' && event.inTime !== '---' 
          ? event.inTime 
          : null
        
        if (defaultTime) {
          // Use displayed time format (MM-DD - HH:MM:SS AM/PM)
          parseDateTimeFromDisplayed(defaultTime)
        } else {
          // Fallback to current date/time
          const now = new Date()
          parseDateTimeFrom24Hour(now.toISOString().slice(0, 19).replace('T', ' '))
        }
      }
      
      showAddModal.value = true
      formData.value.type = type
      setTimeout(() => {
        if (eventDateInput.value) {
          eventDateInput.value.focus()
        }
      }, 100)
    }

    const saveEditEvent = async () => {
      if (!editingEvent.value) return
      
      editError.value = '' // Clear any previous errors
      saving.value = true
      try {
        const eventTime = convertTo24HourFormat()
        await api.clockEvents.update(editingEvent.value.id, eventTime)
        await loadWeekData(weekData.value?.cursorDateTime)
        closeEditModal()
      } catch (err) {
        console.error('Error updating clock event:', err)
        const errorMsg = err.response?.data?.error || err.response?.data?.message || err.message || 'Unknown error'
        const statusCode = err.response?.status || 'N/A'
        editError.value = `Error updating clock event (${statusCode}): ${errorMsg}`
      } finally {
        saving.value = false
      }
    }

    const saveAddEvent = async () => {
      if (!userId.value) {
        addError.value = 'User ID is missing'
        return
      }
      
      addError.value = '' // Clear any previous errors
      saving.value = true
      try {
        const eventTime = convertTo24HourFormat()
        
        await api.clockEvents.create({
          userId: userId.value,
          eventTime: eventTime,
          inOrOut: formData.value.type === 'in' ? 'IN' : 'OUT'
        })
        
        await loadWeekData(weekData.value?.cursorDateTime)
        closeAddModal()
      } catch (err) {
        console.error('Error adding clock event:', err)
        const errorMsg = err.response?.data?.error || err.response?.data?.message || err.message || 'Unknown error'
        const statusCode = err.response?.status || 'N/A'
        addError.value = `Error adding clock event (${statusCode}): ${errorMsg}`
      } finally {
        saving.value = false
      }
    }

    const confirmDelete = async () => {
      if (!deletingEvent.value) return
      
      saving.value = true
      try {
        await api.clockEvents.delete(deletingEvent.value.id)
        await loadWeekData(weekData.value?.cursorDateTime)
        closeDeleteModal()
      } catch (err) {
        alert('Error deleting clock event: ' + (err.response?.data?.error || err.message))
      } finally {
        saving.value = false
      }
    }

    const closeAddModal = () => {
      showAddModal.value = false
      addError.value = '' // Clear errors when closing
      formData.value.eventDate = ''
      formData.value.eventTime = ''
      formData.value.type = 'in'
      timeHours.value = 12
      timeMinutes.value = 0
      timeSeconds.value = 0
      timeAMPM.value = 'AM'
      timeHoursDisplay.value = '12'
      timeMinutesDisplay.value = '00'
      timeSecondsDisplay.value = '00'
    }

    const closeEditModal = () => {
      editingEvent.value = null
      editError.value = '' // Clear errors when closing
      formData.value.eventDate = ''
      formData.value.eventTime = ''
      formData.value.type = 'in'
      timeHours.value = 12
      timeMinutes.value = 0
      timeSeconds.value = 0
      timeAMPM.value = 'AM'
      timeHoursDisplay.value = '12'
      timeMinutesDisplay.value = '00'
      timeSecondsDisplay.value = '00'
    }

    const closeDeleteModal = () => {
      deletingEvent.value = null
    }

    // Keyboard handlers
    const handleKeydown = (e) => {
      if (e.key === 'Escape') {
        if (showAddModal.value) closeAddModal()
        if (editingEvent.value) closeEditModal()
        if (deletingEvent.value) closeDeleteModal()
      }
    }

    onMounted(() => {
      window.addEventListener('keydown', handleKeydown)
      if (userId.value) {
        const cursorDateTime = route.query.cursorDateTime || null
        loadWeekData(cursorDateTime)
      }
    })

    onUnmounted(() => {
      window.removeEventListener('keydown', handleKeydown)
    })

    return {
      userId,
      weekData,
      loading,
      error,
      groupName,
      userName,
      processedEvents,
      totalTime,
      formatDate,
      goPrevWeek,
      goNextWeek,
      goToFirstWeek,
      goToLastWeek,
      isAtEarliestWeek,
      isAtLatestWeek,
      printUrl,
      goToPrint,
      editClockEvent,
      deleteClockEvent,
      addClockEvent,
      showAddModal,
      editingEvent,
      deletingEvent,
      saving,
      addError,
      editError,
      formData,
      eventTimeInput,
      eventDateInput,
      timeHours,
      timeMinutes,
      timeSeconds,
      timeAMPM,
      timeHoursDisplay,
      timeMinutesDisplay,
      timeSecondsDisplay,
      formatTwoDigits,
      incrementTime,
      decrementTime,
      handleTimeInput,
      saveEditEvent,
      saveAddEvent,
      confirmDelete,
      closeAddModal,
      closeEditModal,
      closeDeleteModal,
      addModalContent,
      editModalContent,
      deleteModalContent,
      addModalStyle,
      editModalStyle,
      deleteModalStyle,
      startDrag,
      handleTimeInputFocus,
      handleTimeInputKeydown,
      handleTimeInputBlur,
      handleOverlayMouseDown
    }
  },
}
</script>

<style scoped>
.week-view {
  padding: 20px;
}

.week-header {
  margin-bottom: 20px;
}

.week-navigation {
  margin-top: 0;
  display: flex;
  align-items: center;
  gap: 20px;
}

.week-nav {
  border: 0;
  padding: 0;
  display: inline-flex;
  align-items: stretch;
  gap: 20px;
}

.week-nav-controls {
  display: inline-flex;
  align-items: stretch;
  gap: 10px;
}

.week-range {
  font-weight: bold;
  min-width: 280px;
  text-align: center;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 0 12px;
}

.week-info {
  margin-top: 20px;
}

.week-info-table {
  width: 100%;
  max-width: 800px;
  border-collapse: collapse;
  border: 1px solid #ccc;
  table-layout: fixed;
}

.week-info-table td {
  border: 1px solid #ccc;
  padding: 5px 10px;
}

.week-info-table .name-cell {
  text-align: right;
  padding-right: 5px;
  padding-left: 2px;
  background-color: #f2f2f2;
  font-weight: bold;
  width: 90px;
  white-space: nowrap;
}

.week-info-table .name-cell-name {
  width: 70px;
}

.week-info-table .data-cell {
  background-color: #fff;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.week-navigation button {
  font-size: 16px;
  font-weight: bold;
  padding: 5px 10px;
  cursor: pointer;
  background-color: #f2f2f2;
  border: 1px solid #ccc;
  border-radius: 4px;
}

.week-navigation button:hover:not(:disabled) {
  background-color: #f0f0f0;
}

.week-navigation button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.week-navigation .print-button {
  background-color: #007bff;
  color: white;
  border-color: #007bff;
  font-size: 14px;
  width: 170px;
  height: 36px;
  padding: 8px 16px;
  line-height: 1.2;
  box-sizing: border-box;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.week-nav-controls .pagination-arrow {
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
  height: 36px;
}

.week-nav-controls .pagination-arrow:hover:not(:disabled) {
  background-color: #0056b3;
}

.week-nav-controls .pagination-arrow:disabled {
  background-color: #ccc;
  cursor: not-allowed;
  opacity: 0.6;
}

.week-navigation .print-button:hover {
  background-color: #0056b3;
}

.error-message {
  color: red;
  padding: 10px;
  background-color: #ffe6e6;
  border-radius: 4px;
}

.no-records {
  text-align: center;
  padding: 20px;
  color: #666;
  font-style: italic;
}

.total-row {
  font-weight: bold;
  background-color: #e0e0e0;
}

.week-table {
  width: 100%;
  max-width: 800px;
  border-collapse: collapse;
  margin-top: 20px;
}

.week-table th,
.week-table td {
  border: 1px solid #ccc;
  padding: 8px;
  text-align: left;
  position: relative;
}

.week-table th {
  background-color: #f2f2f2;
  font-weight: bold;
  position: relative;
}

.week-table th:nth-child(2),
.week-table th:nth-child(3) {
  text-align: left;
}

.header-add-btn {
  width: 20px;
  height: 20px;
  padding: 0;
  margin: 0;
  margin-left: 10px;
  border: 1px solid #ccc;
  border-radius: 2px;
  cursor: pointer;
  font-size: 12px;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: white;
  color: #28a745;
  float: right;
}

.header-add-btn:hover {
  background-color: #e6ffe6;
  opacity: 0.8;
}

.missing {
  color: #999;
  font-style: italic;
}

.virtual-clock-in {
  background-color: lightgreen;
}

.virtual-clock-out {
  background-color: lightblue;
}

.missing-time {
  background-color: lightsalmon;
}

.time-value {
  display: inline-block;
}

.cell-actions {
  display: inline-flex;
  gap: 2px;
  float: right;
  margin-left: 10px;
}

.action-btn {
  width: 20px;
  height: 20px;
  padding: 0;
  margin: 0;
  border: 1px solid #ccc;
  border-radius: 2px;
  cursor: pointer;
  font-size: 12px;
  line-height: 1;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: white;
}

.action-btn:hover {
  opacity: 0.8;
}

.edit-btn {
  color: #007bff;
}

.edit-btn:hover {
  background-color: #e7f3ff;
}

.delete-btn {
  color: #dc3545;
}

.delete-btn:hover {
  background-color: #ffe6e6;
}

.add-btn {
  color: #28a745;
}

.add-btn:hover {
  background-color: #e6ffe6;
}

/* Modal Styles */
.modal-overlay {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: rgba(0, 0, 0, 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background-color: white;
  padding: 20px;
  border-radius: 8px;
  max-width: 600px;
  min-width: 500px;
  width: 90%;
  max-height: 90vh;
  overflow-y: auto;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
  position: relative;
  z-index: 1001;
  transition: transform 0s;
}

.modal-title-box {
  background-color: #f5f5f5;
  padding: 15px;
  margin: -20px -20px 20px -20px;
  border-radius: 8px 8px 0 0;
  text-align: center;
}

.modal-title-box h3 {
  margin: 0;
  font-size: 18px;
  font-weight: bold;
  color: #333;
}

.modal-body {
  margin-bottom: 20px;
}

.modal-body p {
  margin: 10px 0;
}

.warning-text {
  color: #dc3545;
  font-weight: bold;
}

.form-group {
  margin-bottom: 15px;
}

.form-group label {
  display: block;
  margin-bottom: 5px;
  font-weight: bold;
  color: #333;
}

.form-group input,
.form-group select {
  width: 100%;
  padding: 8px;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
  box-sizing: border-box;
}

.form-group input:focus,
.form-group select:focus {
  outline: none;
  border-color: #007bff;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 20px;
}

.form-actions button {
  padding: 8px 16px;
  border: none;
  border-radius: 4px;
  cursor: pointer;
  font-size: 14px;
}

.form-actions button[type="submit"] {
  background-color: #007bff;
  color: white;
}

.form-actions button[type="submit"]:hover:not(:disabled) {
  background-color: #0056b3;
}

.form-actions button[type="button"] {
  background-color: #6c757d;
  color: white;
}

.form-actions button[type="button"]:hover {
  background-color: #5a6268;
}

.error-box {
  margin-top: 15px;
  padding: 10px;
  border: 1px solid #dc3545;
  border-radius: 4px;
  background-color: #fff5f5;
  color: #dc3545;
  font-size: 14px;
  line-height: 1.4;
  text-align: left;
}

.form-actions button:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.delete-confirm-btn {
  background-color: #dc3545 !important;
  color: white !important;
}

.delete-confirm-btn:hover:not(:disabled) {
  background-color: #c82333 !important;
}

/* Time Widget Styles */
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
</style>
