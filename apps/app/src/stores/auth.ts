import { ref, computed } from 'vue'
import { defineStore } from 'pinia'
import { apiFetch, ApiError, setAuthTokenUpdater } from '@/services/api'
import { setEchoAuthToken } from '@/services/echo'

export type User = {
  id: number
  name: string
  email: string
  address?: string | null
  profile_photo_url?: string | null
}

const ACCESS_TOKEN_KEY = 'auth.access_token'
const REFRESH_TOKEN_KEY = 'auth.refresh_token'
const EXPIRES_AT_KEY = 'auth.expires_at'
const USER_KEY = 'auth.user'

export const useAuthStore = defineStore('auth', () => {
  const token = ref<string | null>(localStorage.getItem(ACCESS_TOKEN_KEY))
  const refreshToken = ref<string | null>(localStorage.getItem(REFRESH_TOKEN_KEY))
  const expiresAt = ref<string | null>(localStorage.getItem(EXPIRES_AT_KEY))
  const user = ref<User | null>(
    localStorage.getItem(USER_KEY) ? JSON.parse(localStorage.getItem(USER_KEY) as string) : null
  )

  const isAuthenticated = computed(() => Boolean(token.value))

  function setTokens(nextToken: string | null, nextRefreshToken: string | null, nextExpiresAt: string | null) {
    token.value = nextToken
    refreshToken.value = nextRefreshToken
    expiresAt.value = nextExpiresAt

    setEchoAuthToken(nextToken)

    if (nextToken) {
      localStorage.setItem(ACCESS_TOKEN_KEY, nextToken)
    } else {
      localStorage.removeItem(ACCESS_TOKEN_KEY)
    }

    if (nextRefreshToken) {
      localStorage.setItem(REFRESH_TOKEN_KEY, nextRefreshToken)
    } else {
      localStorage.removeItem(REFRESH_TOKEN_KEY)
    }

    if (nextExpiresAt) {
      localStorage.setItem(EXPIRES_AT_KEY, nextExpiresAt)
    } else {
      localStorage.removeItem(EXPIRES_AT_KEY)
    }
  }

  function setSession(
    nextToken: string | null,
    nextRefreshToken: string | null,
    nextExpiresAt: string | null,
    nextUser: User | null
  ) {
    setTokens(nextToken, nextRefreshToken, nextExpiresAt)
    user.value = nextUser

    if (nextUser) {
      localStorage.setItem(USER_KEY, JSON.stringify(nextUser))
    } else {
      localStorage.removeItem(USER_KEY)
    }
  }

  async function register(payload: { name: string; email: string; password: string }) {
    const data = await apiFetch<{ user: User }>('/auth/register', {
      method: 'POST',
      body: JSON.stringify(payload),
    })

    return data.user
  }

  async function logout() {
    try {
      await apiFetch('/auth/logout', { method: 'POST' })
    } catch (error) {
      if (error instanceof ApiError && error.status === 401) {
        try {
          await fetchMe()
          await apiFetch('/auth/logout', { method: 'POST' })
        } catch (retryError) {
          if (!(retryError instanceof ApiError)) {
            throw retryError
          }
        }
      } else if (!(error instanceof ApiError)) {
        throw error
      }
    }

    setSession(null, null, null, null)
  }

  async function fetchMe() {
    const data = await apiFetch<User>('/me')
    setSession(token.value, refreshToken.value, expiresAt.value, data)
  }

  setAuthTokenUpdater((payload) => {
    setTokens(payload.accessToken, payload.refreshToken, payload.expiresAt)
  })

  return {
    token,
    refreshToken,
    expiresAt,
    user,
    isAuthenticated,
    register,
    logout,
    fetchMe,
    setSession,
  }
})
