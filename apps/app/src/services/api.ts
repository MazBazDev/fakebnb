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

function getAuthToken(): string | null {
  return localStorage.getItem('auth.token')
}

export async function apiFetch<T>(
  path: string,
  options: RequestInit = {}
): Promise<T> {
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

  if (!response.ok) {
    const payload = data as ApiErrorPayload | null
    const message =
      payload?.message ?? response.statusText ?? 'Une erreur est survenue.'
    throw new ApiError(response.status, message, payload ?? undefined)
  }

  return data as T
}
