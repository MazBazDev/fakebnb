import { getOAuthConfig } from '@/services/oauth'

export type ApiErrorPayload = {
  message: string
  errors?: Record<string, string[]>
}

export class ApiError extends Error {
  status: number
  payload?: ApiErrorPayload

  constructor(status: number, message: string, payload?: ApiErrorPayload) {
    super(message)
    this.status = status
    this.payload = payload
  }
}

const API_BASE = import.meta.env.VITE_API_URL ?? '/api/v1'
const ACCESS_TOKEN_KEY = 'auth.access_token'
const REFRESH_TOKEN_KEY = 'auth.refresh_token'
const EXPIRES_AT_KEY = 'auth.expires_at'

type TokenUpdate = {
  accessToken: string | null
  refreshToken: string | null
  expiresAt: string | null
}

type TokenResponse = {
  access_token: string
  refresh_token?: string | null
  expires_in: number
}

let authTokenUpdater: ((payload: TokenUpdate) => void) | null = null
let refreshPromise: Promise<string | null> | null = null

export function setAuthTokenUpdater(updater: (payload: TokenUpdate) => void) {
  authTokenUpdater = updater
}

function getAuthToken(): string | null {
  return localStorage.getItem(ACCESS_TOKEN_KEY)
}

function getRefreshToken(): string | null {
  return localStorage.getItem(REFRESH_TOKEN_KEY)
}

function persistTokens(payload: TokenUpdate) {
  if (payload.accessToken) {
    localStorage.setItem(ACCESS_TOKEN_KEY, payload.accessToken)
  } else {
    localStorage.removeItem(ACCESS_TOKEN_KEY)
  }

  if (payload.refreshToken) {
    localStorage.setItem(REFRESH_TOKEN_KEY, payload.refreshToken)
  } else {
    localStorage.removeItem(REFRESH_TOKEN_KEY)
  }

  if (payload.expiresAt) {
    localStorage.setItem(EXPIRES_AT_KEY, payload.expiresAt)
  } else {
    localStorage.removeItem(EXPIRES_AT_KEY)
  }

  authTokenUpdater?.(payload)
}

function isTokenResponse(data: unknown): data is TokenResponse {
  if (!data || typeof data !== 'object') return false
  const payload = data as Record<string, unknown>
  return (
    typeof payload.access_token === 'string' &&
    typeof payload.expires_in === 'number'
  )
}

async function refreshAccessToken(): Promise<string | null> {
  if (refreshPromise) {
    return refreshPromise
  }

  const refreshToken = getRefreshToken()
  if (!refreshToken) {
    return null
  }

  refreshPromise = (async () => {
    const config = getOAuthConfig()
    if (!config.clientId) {
      return null
    }

    const response = await fetch(config.tokenUrl, {
      method: 'POST',
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        grant_type: 'refresh_token',
        refresh_token: refreshToken,
        client_id: config.clientId,
        scope: '',
      }),
    })

    const text = await response.text()
    let data: unknown = null
    if (text) {
      try {
        data = JSON.parse(text)
      } catch {
        data = null
      }
    }

    if (!response.ok || !isTokenResponse(data)) {
      persistTokens({ accessToken: null, refreshToken: null, expiresAt: null })
      return null
    }

    const expiresAt = new Date(Date.now() + data.expires_in * 1000).toISOString()
    persistTokens({
      accessToken: data.access_token,
      refreshToken: data.refresh_token ?? refreshToken,
      expiresAt,
    })

    return data.access_token
  })()

  try {
    return await refreshPromise
  } finally {
    refreshPromise = null
  }
}

export async function apiFetch<T>(path: string, options: RequestInit = {}): Promise<T> {
  return apiFetchWithRetry<T>(path, options, true)
}

async function apiFetchWithRetry<T>(
  path: string,
  options: RequestInit,
  allowRefresh: boolean
): Promise<T> {
  const { data } = await apiFetchWithRetryRaw(path, options, allowRefresh, false)
  return data as T
}

async function apiFetchWithRetryRaw(
  path: string,
  options: RequestInit,
  allowRefresh: boolean,
  allowNotModified: boolean
): Promise<{ response: Response; data: unknown }> {
  const headers = new Headers(options.headers ?? {})
  headers.set('Accept', 'application/json')

  if (options.body && !headers.has('Content-Type')) {
    headers.set('Content-Type', 'application/json')
  }

  const token = getAuthToken()
  if (token) {
    headers.set('Authorization', `Bearer ${token}`)
  }

  const response = await fetch(`${API_BASE}${path}`, {
    ...options,
    headers,
  })

  const text = await response.text()
  let data: unknown = null
  if (text) {
    try {
      data = JSON.parse(text)
    } catch {
      data = null
    }
  }

  if (!response.ok && !(allowNotModified && response.status === 304)) {
    const canAttemptRefresh = allowRefresh && response.status === 401 && !path.startsWith('/auth/')
    if (canAttemptRefresh) {
      const refreshed = await refreshAccessToken()
      if (refreshed) {
        return apiFetchWithRetryRaw(path, options, false, allowNotModified)
      }
    }

    const payload = data as ApiErrorPayload | null
    const message = payload?.message ?? response.statusText ?? 'Une erreur est survenue.'
    throw new ApiError(response.status, message, payload ?? undefined)
  }

  return { response, data }
}
