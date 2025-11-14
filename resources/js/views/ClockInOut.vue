<template>
  <div class="clock-in-out">
    <div class="login-form-container">
      <h2>CLOCK IN / OUT</h2>
      <form @submit.prevent="handleClockAction">
        <input 
          ref="userCodeInput"
          v-model="userCode" 
          placeholder="Enter user code" 
          required 
          :disabled="loading"
        />
        <button type="submit" :disabled="loading">
          {{ loading ? 'Processing...' : 'Clock In/Out' }}
        </button>
      </form>
      
      <div v-if="message" :class="messageType" class="message">
        {{ message }}
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, watch } from 'vue'
import { useRoute } from 'vue-router'
import api from '../api/index.js'

export default {
  name: 'ClockInOut',
  setup() {
    const userCode = ref('')
    const message = ref('')
    const messageType = ref('')
    const loading = ref(false)
    const userCodeInput = ref(null)

    const handleClockAction = async () => {
      if (!userCode.value.trim()) return
      
      loading.value = true
      message.value = ''
      messageType.value = ''

      try {
        const response = await api.clockEvents.clockInOut(userCode.value.trim())
        message.value = response.data.message || `Clocked ${response.data.clockOp} successfully`
        messageType.value = 'success'
        userCode.value = ''
        
        // Focus the input again after successful clock action
        setTimeout(() => {
          if (userCodeInput.value) {
            userCodeInput.value.focus()
          }
        }, 100)
        
        // Clear success message after 5 seconds
        setTimeout(() => {
          message.value = ''
        }, 5000)
      } catch (error) {
        const errorMsg = error.response?.data?.error || error.response?.data?.message || 'Error clocking in/out'
        message.value = errorMsg
        messageType.value = 'error'
        
        // Focus the input again after error
        setTimeout(() => {
          if (userCodeInput.value) {
            userCodeInput.value.focus()
          }
        }, 100)
        
        // Clear error message after 5 seconds
        setTimeout(() => {
          message.value = ''
        }, 5000)
      } finally {
        loading.value = false
      }
    }

    const route = useRoute()
    
    const focusInput = () => {
      setTimeout(() => {
        if (userCodeInput.value) {
          userCodeInput.value.focus()
        }
      }, 100)
    }

    onMounted(() => {
      focusInput()
    })

    // Watch for route changes to focus input when navigating to this tab
    watch(() => route.path, (newPath) => {
      if (newPath === '/') {
        focusInput()
      }
    })

    return {
      userCode,
      message,
      messageType,
      loading,
      handleClockAction,
      userCodeInput,
    }
  },
}
</script>

<style scoped>
.clock-in-out {
  max-width: 800px;
  margin: 0;
  padding: 20px;
}

h2 {
  margin-bottom: 20px;
  text-align: center;
}

.login-form-container {
  padding: 20px;
  display: flex;
  flex-direction: column;
  align-items: center;
}

form {
  margin-bottom: 20px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  align-items: center;
}

input {
  width: 200px;
  padding: 10px;
  font-size: 16px;
  border: 1px solid #ccc;
  border-radius: 4px;
}

input:disabled {
  background-color: #f0f0f0;
  cursor: not-allowed;
}

button {
  width: 200px;
  padding: 10px 20px;
  font-size: 16px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 4px;
  cursor: pointer;
}

button:hover:not(:disabled) {
  background-color: #0056b3;
}

button:disabled {
  background-color: #ccc;
  cursor: not-allowed;
}

.message {
  padding: 10px;
  margin: 10px 0;
  border-radius: 4px;
  text-align: center;
}

.success {
  background-color: lightgreen;
  color: darkgreen;
}

.error {
  background-color: lightsalmon;
  color: darkred;
}
</style>
