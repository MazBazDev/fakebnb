import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { apiFetch, ApiError } from '@/services/api'

export type User = {
  id: number
  name: string
  email: string
  address?: string | null
  profile_photo_url?: string | null
}

const TOKEN_KEY = 'auth.token'
const USER_KEY = 'auth.user'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem(TOKEN_KEY))
  const user = ref<User | null>(
    localStorage.getItem(USER_KEY) ? JSON.parse(localStorage.getItem(USER_KEY) as string) : null
  )

  const isAuthenticated = computed(() => Boolean(token.value))

  function setSession(nextToken: string | null, nextUser: User | null) {
    token.value = nextToken
    user.value = nextUser

    if (nextToken) {
      localStorage.setItem(TOKEN_KEY, nextToken)
    } else {
      localStorage.removeItem(TOKEN_KEY)
    }

    if (nextUser) {
      localStorage.setItem(USER_KEY, JSON.stringify(nextUser))
    } else {
      localStorage.removeItem(USER_KEY)
    }
  }

  async function login(payload: { email: string; password: string }) {
    const data = await apiFetch<{ token: string; user: User }>('/auth/login', {
      method: 'POST',
      body: JSON.stringify(payload),
    })

    setSession(data.token, data.user)
  }

  async function register(payload: { name: string; email: string; password: string }) {
    const data = await apiFetch<{ token: string; user: User }>('/auth/register', {
      method: 'POST',
      body: JSON.stringify(payload),
    })

    setSession(data.token, data.user)
  }

  async function logout() {
    try {
      await apiFetch('/auth/logout', { method: 'POST' })
    } catch (error) {
      if (!(error instanceof ApiError)) {
        throw error
      }
    }

    setSession(null, null)
  }

  async function fetchMe() {
    const data = await apiFetch<User>('/me')
    setSession(token.value, data)
  }

  return {
    token,
    user,
    isAuthenticated,
    login,
    register,
    logout,
    fetchMe,
    setSession,
  }
})
