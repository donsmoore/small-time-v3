// API client
import axios from 'axios'

// Laravel API URL - use same origin as the app (Apache)
const API_BASE_URL = import.meta.env.VITE_API_URL || window.location.origin

const apiClient = axios.create({
  baseURL: `${API_BASE_URL}/api/v1`,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
})

// Add error handling for debugging
apiClient.interceptors.response.use(
  response => response,
  error => {
    console.error('API Error:', error);
    console.error('Request URL:', error.config?.url);
    console.error('Response:', error.response?.data);
    return Promise.reject(error);
  }
);

export default {
  clockEvents: {
    clockInOut: (userCode) => apiClient.post('/clock/action', { userCode }),
    getLastEvents: (userId = null) => {
      const url = userId ? `/clock-events/last/${userId}` : '/clock-events/last';
      return apiClient.get(url);
    },
    create: (data) => apiClient.post('/clock-events', data),
    update: (id, eventTime) => apiClient.put(`/clock-events/${id}`, { eventTime }),
    delete: (id) => apiClient.delete(`/clock-events/${id}`),
  },
  week: {
    get: (userId, params = {}) => {
      const queryString = new URLSearchParams(params).toString()
      return apiClient.get(`/week/${userId}${queryString ? '?' + queryString : ''}`)
    },
  },
  users: {
    getAll: () => apiClient.get('/users'),
    create: (data) => apiClient.post('/users', data),
    update: (id, data) => apiClient.put(`/users/${id}`, data),
    delete: (id) => apiClient.delete(`/users/${id}`),
    getNameColumnWidth: () => apiClient.get('/users/column-width/name'),
  },
  groups: {
    getAll: () => apiClient.get('/groups'),
    create: (data) => apiClient.post('/groups', data),
    update: (id, data) => apiClient.put(`/groups/${id}`, data),
    delete: (id) => apiClient.delete(`/groups/${id}`),
    getColumnWidth: () => apiClient.get('/groups/column-width/groupName'),
  },
}
