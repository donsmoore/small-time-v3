<template>
  <div class="print-view">
    <div class="no-print">
      <button @click="window.print()">Print</button>
      <button @click="closeTab" class="close-btn">Close</button>
      <br><br>
    </div>

    <div class="timesheet-header">
      <h2 class="timesheet-title">Timesheet</h2>
      <table class="info-table">
        <tr>
          <td class="info-label">Employer:</td>
          <td class="info-value">{{ groupName }}</td>
          <td class="info-label">Employee:</td>
          <td class="info-value">{{ userName }}</td>
        </tr>
        <tr>
          <td class="info-label">Week:</td>
          <td class="info-value">{{ weekStartDate }} through {{ weekEndDate }}</td>
          <td class="info-label">Week Start:</td>
          <td class="info-value">{{ weekStartDOW }} - {{ weekStartTime }}</td>
        </tr>
      </table>
    </div>

    <table class="timesheet-table">
      <thead>
        <tr>
          <th class="index-col">#</th>
          <th class="day-col">Day</th>
          <th class="time-col">Clock In</th>
          <th class="time-col">Clock Out</th>
          <th class="worked-col">Time Worked<br><span style="font-size: 0.8em">HH:MM:SS</span></th>
        </tr>
      </thead>
      <tbody>
        <tr v-if="events.length === 0">
          <td colspan="5" style="text-align: center; padding: 10px;">
            No timeclock records found for this week
          </td>
        </tr>
        <tr v-for="(event, index) in events" :key="index">
          <td>{{ index + 1 }}</td>
          <td>{{ getDayOfWeek(event.inTimeRaw) }}</td>
          <td>{{ event.inTime }}</td>
          <td>{{ event.outTime }}</td>
          <td class="time-right">{{ event.timeWorked }}</td>
        </tr>
      </tbody>
      <tfoot v-if="events.length > 0 && totalTime">
        <tr class="total-row">
          <td colspan="4"><strong>Total Time</strong></td>
          <td class="time-right"><strong>{{ totalTime }}</strong></td>
        </tr>
      </tfoot>
    </table>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/index.js'

export default {
  name: 'PrintView',
  setup() {
    const route = useRoute()
    const userId = ref(route.query.userId ? parseInt(route.query.userId) : null)
    const cursorDateTime = ref(route.query.cursorDateTime || null)
    const weekData = ref(null)
    const userName = ref('')
    const groupName = ref('')
    const loading = ref(false)

    const formatDate = (dateString) => {
      if (!dateString) return ''
      return dateString.substring(0, 10)
    }

    // Parse 12-hour format: YYYY-MM-DD HH:MM:SS AM/PM
    const parseDateTime12Hour = (dateTimeStr) => {
      if (!dateTimeStr || dateTimeStr === '---' || dateTimeStr === '????-??-?? ??:??:??') {
        return null
      }
      
      const parts = dateTimeStr.split(' ')
      const datePart = parts[0] // YYYY-MM-DD
      const timePart = parts[1] // HH:MM:SS
      const ampm = parts[2] // AM/PM
      
      const [year, month, day] = datePart.split('-')
      const [hours, minutes, seconds] = timePart.split(':')
      
      let hour24 = parseInt(hours)
      if (ampm === 'PM' && hour24 !== 12) {
        hour24 += 12
      } else if (ampm === 'AM' && hour24 === 12) {
        hour24 = 0
      }
      
      return new Date(year, month - 1, day, hour24, parseInt(minutes), parseInt(seconds))
    }

    const getDayOfWeek = (dateTime) => {
      if (!dateTime || dateTime === '---' || dateTime === '????-??-?? ??:??:??') {
        return '---'
      }
      
      try {
        const date = new Date(dateTime)
        if (isNaN(date.getTime())) return '---'
        const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
        return days[date.getDay()]
      } catch (e) {
        return '---'
      }
    }

    const getOrdinal = (n) => {
      const s = ["th", "st", "nd", "rd"];
      const v = n % 100;
      return n + (s[(v - 20) % 10] || s[v] || s[0]);
    }

    const formatDateTime = (dateTime) => {
      if (!dateTime || dateTime === '---') return '---'
      const date = new Date(dateTime)
      
      const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]
      const monthName = months[date.getMonth()]
      const day = getOrdinal(date.getDate())
      
      // Convert to 12-hour format
      let hours = date.getHours()
      const ampm = hours >= 12 ? 'pm' : 'am'
      hours = hours % 12
      hours = hours ? hours : 12 // the hour '0' should be '12'
      const minutes = String(date.getMinutes()).padStart(2, '0')
      
      return `${monthName} ${day} ${hours}:${minutes}${ampm}`
    }

    const exceeds24Hours = (inTime, outTime) => {
      // input is now raw string
      if (!inTime || !outTime || inTime === '---' || outTime === '---') {
        return false
      }
      
      try {
        const inDate = new Date(inTime)
        const outDate = new Date(outTime)
        
        if (isNaN(inDate.getTime()) || isNaN(outDate.getTime())) return false
        
        const diffMs = outDate - inDate
        const diffSec = Math.floor(diffMs / 1000)
        
        // Check if difference exceeds 24 hours (86400 seconds)
        return diffSec > 86400
      } catch (e) {
        return false
      }
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

    const processEvents = (events, weekData) => {
      if (!events || events.length === 0) return []
      
      const pairs = []
      let currentPair = { inTimeRaw: null, outTimeRaw: null, inId: null, outId: null }
      
      // Filter out events with empty/null inOrOut values
      const validEvents = events.filter(e => e.inOrOut && (e.inOrOut === 'IN' || e.inOrOut === 'OUT'))
      
      // Check for beginning of week transition
      const eventBeforeWeekStart = weekData?.eventBeforeWeekStart
      const firstValidEvent = validEvents.length > 0 ? validEvents[0] : null
      const isBeginningTransition = firstValidEvent && 
                                    firstValidEvent.inOrOut === 'OUT' && 
                                    eventBeforeWeekStart && 
                                    eventBeforeWeekStart.inOrOut === 'IN' && 
                                    isWithin24Hours(eventBeforeWeekStart.eventTime, firstValidEvent.eventTime)
      
      // Check for end of week transition
      const eventAfterWeekEnd = weekData?.eventAfterWeekEnd
      const lastValidEvent = validEvents.length > 0 ? validEvents[validEvents.length - 1] : null
      const isEndTransition = lastValidEvent && 
                              lastValidEvent.inOrOut === 'IN' && 
                              eventAfterWeekEnd && 
                              eventAfterWeekEnd.inOrOut === 'OUT' && 
                              isWithin24Hours(lastValidEvent.eventTime, eventAfterWeekEnd.eventTime)
      
      events.forEach((event, index) => {
        if (event.inOrOut === 'IN') {
          if (currentPair.inTimeRaw) {
            // Start new pair if we already have an IN
            pairs.push({ 
              ...currentPair, 
              inTime: currentPair.inId === 0 ? '????-??-?? ??:??:??' : formatDateTime(currentPair.inTimeRaw),
              outTime: (!currentPair.outTimeRaw || currentPair.outId === 0 || currentPair.outId === null) ? '????-??-?? ??:??:??' : formatDateTime(currentPair.outTimeRaw),
              inTimeRaw: currentPair.inTimeRaw,
              outTimeRaw: currentPair.outTimeRaw,
              timeWorked: '---' 
            })
            currentPair = { inTimeRaw: null, outTimeRaw: null, inId: null, outId: null }
          }
          currentPair.inTimeRaw = event.eventTime
          currentPair.inId = event.id
          
          // Check if this is the last valid event and it's an end transition
          const validInIndex = validEvents.findIndex(e => e.id === event.id && e.inOrOut === 'IN')
          if (validInIndex === validEvents.length - 1 && isEndTransition) {
            currentPair.inId = -2 // End of week transition
          }
        } else if (event.inOrOut === 'OUT') {
          // Check if this is the first valid OUT event and it's a beginning transition
          const validOutIndex = validEvents.findIndex(e => e.id === event.id && e.inOrOut === 'OUT')
          if (validOutIndex === 0 && isBeginningTransition && !currentPair.inTimeRaw) {
            currentPair.inId = -1 // Beginning of week transition (virtual clock in)
            // Use the week start date/time as the virtual clock in
            currentPair.inTimeRaw = weekData?.cursorWeekStartDateTime || ''
          }
          
          // If we don't have an IN yet, this shouldn't happen normally, but handle it
          if (!currentPair.inTimeRaw) {
            currentPair.inTimeRaw = null // Missing IN
            currentPair.inId = 0
          }
          
          currentPair.outTimeRaw = event.eventTime
          currentPair.outId = event.id
          
          // Check if the time difference exceeds 24 hours
          if (exceeds24Hours(currentPair.inTimeRaw, currentPair.outTimeRaw)) {
            // Split into two entries: IN with missing OUT, then missing IN with OUT
            // First entry: Clock In with missing Clock Out
            pairs.push({
              inTime: currentPair.inId === 0 ? '????-??-?? ??:??:??' : formatDateTime(currentPair.inTimeRaw),
              inTimeRaw: currentPair.inTimeRaw,
              inId: currentPair.inId,
              outTime: '????-??-?? ??:??:??',
              outTimeRaw: null,
              outId: 0,
              timeWorked: '---'
            })
            
            // Second entry: Missing Clock In with Clock Out
            pairs.push({
              inTime: '????-??-?? ??:??:??',
              inTimeRaw: null,
              inId: 0,
              outTime: formatDateTime(currentPair.outTimeRaw),
              outTimeRaw: currentPair.outTimeRaw,
              outId: currentPair.outId,
              timeWorked: '---'
            })
          } else {
            // Normal pair within 24 hours
            pairs.push({ 
              ...currentPair, 
              inTime: currentPair.inId === 0 ? '????-??-?? ??:??:??' : formatDateTime(currentPair.inTimeRaw),
              outTime: currentPair.outId === 0 ? '????-??-?? ??:??:??' : formatDateTime(currentPair.outTimeRaw),
              inTimeRaw: currentPair.inTimeRaw,
              outTimeRaw: currentPair.outTimeRaw,
              timeWorked: calculateTimeWorked(currentPair.inTimeRaw, currentPair.outTimeRaw) 
            })
          }
          
          currentPair = { inTimeRaw: null, outTimeRaw: null, inId: null, outId: null }
        }
      })
      
      // Handle last IN without OUT
      if (currentPair.inTimeRaw) {
        // Check if this is an end transition (last event is IN and there's an OUT after week end)
        if (isEndTransition && currentPair.inId === -2) {
          // Use the week end date/time as the virtual clock out
          currentPair.outTimeRaw = weekData?.cursorWeekEndDateTime || ''
          currentPair.outId = -2 // Mark as end transition
          // Calculate time worked using the virtual clock out
          pairs.push({
            ...currentPair,
            inTime: formatDateTime(currentPair.inTimeRaw),
            outTime: formatDateTime(currentPair.outTimeRaw),
            timeWorked: calculateTimeWorked(currentPair.inTimeRaw, currentPair.outTimeRaw)
          })
        } else {
          pairs.push({ 
            ...currentPair, 
            inTime: currentPair.inId === 0 ? '????-??-?? ??:??:??' : formatDateTime(currentPair.inTimeRaw),
            outTime: '---', 
            inTimeRaw: currentPair.inTimeRaw,
            outTimeRaw: null,
            timeWorked: '---' 
          })
        }
      }
      
      return pairs
    }

    const calculateTimeWorked = (inTime, outTime) => {
      // Input is now raw ISO string or similar
      if (!inTime || !outTime || inTime === '---' || outTime === '---') return '---'
      
      try {
        const inDate = new Date(inTime)
        const outDate = new Date(outTime)
        
        if (isNaN(inDate.getTime()) || isNaN(outDate.getTime())) return '---'
        
        const diffMs = outDate - inDate
        
        if (isNaN(diffMs) || diffMs < 0) return '---'
        
        const diffSec = Math.floor(diffMs / 1000)
        const hours = Math.floor(diffSec / 3600)
        const minutes = Math.floor((diffSec % 3600) / 60)
        const seconds = diffSec % 60
        
        return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
      } catch (e) {
        return '---'
      }
    }

    const events = computed(() => {
      if (!weekData.value || !weekData.value.events) return []
      return processEvents(weekData.value.events, weekData.value)
    })

    const weekStartDate = computed(() => {
      return weekData.value ? formatDate(weekData.value.cursorWeekStartDateTime) : ''
    })

    const weekEndDate = computed(() => {
      return weekData.value ? formatDate(weekData.value.cursorWeekEndDateTime) : ''
    })

    const weekStartDOW = computed(() => {
      return weekData.value ? weekData.value.clockWeekStartDOW : ''
    })

    const weekStartTime = computed(() => {
      return weekData.value ? weekData.value.clockWeekStartTime : ''
    })

    const totalTime = computed(() => {
      if (!events.value || events.value.length === 0) return ''
      
      let totalSeconds = 0
      events.value.forEach(event => {
        if (event.timeWorked !== '---') {
          const parts = event.timeWorked.split(':')
          if (parts.length === 3) {
            totalSeconds += parseInt(parts[0]) * 3600 + parseInt(parts[1]) * 60 + parseInt(parts[2])
          }
        }
      })
      
      const hours = Math.floor(totalSeconds / 3600)
      const minutes = Math.floor((totalSeconds % 3600) / 60)
      const seconds = totalSeconds % 60
      
      return `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`
    })

    const loadPrintData = async () => {
      if (!userId.value) return
      
      loading.value = true
      try {
        const params = {}
        if (cursorDateTime.value) params.cursorDateTime = cursorDateTime.value
        
        const response = await api.week.get(userId.value, params)
        weekData.value = response.data.data
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
      } catch (err) {
        console.error('Error loading print data:', err)
      } finally {
        loading.value = false
      }
    }

    const closeTab = () => {
      window.close()
    }

    onMounted(() => {
      loadPrintData()
    })

    return {
      userId,
      weekData,
      userName,
      groupName,
      events,
      weekStartDate,
      weekEndDate,
      weekStartDOW,
      weekStartTime,
      totalTime,
      window,
      closeTab,
      getDayOfWeek,
    }
  },
}
</script>

<style scoped>
.print-view {
  padding: 20px;
  font-family: Arial, sans-serif;
}

.no-print {
  margin-bottom: 20px;
}

.no-print button {
  padding: 10px 20px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

.no-print button:hover {
  background-color: #0056b3;
}

.close-btn {
  background-color: #dc3545 !important;
  margin-left: 10px;
}

.close-btn:hover {
  background-color: #c82333 !important;
}

.timesheet-header {
  margin-bottom: 20px;
}

.timesheet-title {
  margin-bottom: 10px;
  font-size: 24px;
  font-weight: bold;
  text-align: center;
}

.info-table {
  width: 100%;
  border-collapse: collapse;
  border: 1px solid #ccc;
  table-layout: fixed;
}

.info-table td {
  border: 1px solid #d0d0d0;
  padding: 8px;
}

.info-label {
  font-weight: bold;
  background-color: #f5f5f5;
  width: 150px;
  text-align: right;
}

.info-value {
  background-color: #fff;
  width: calc((100% - 300px) / 2);
}

.timesheet-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 20px;
}

.timesheet-table th,
.timesheet-table td {
  border: 1px solid #000;
  padding: 8px;
  text-align: left;
}

.timesheet-table th {
  background-color: #f0f0f0;
  font-weight: bold;
}

.time-right {
  text-align: right;
}

/* Column Widths */
.index-col {
  width: 40px;
  text-align: center;
}

.day-col {
  width: 120px;
}

.time-col {
  width: 160px;
  white-space: nowrap;
}

.worked-col {
  width: 150px;
  text-align: right;
  white-space: nowrap;
}

.total-row {
  font-weight: bold;
  background-color: #e0e0e0;
}

@media print {
  .no-print {
    display: none;
  }
  
  body {
    margin: 0;
    padding: 20px;
  }
}
</style>

