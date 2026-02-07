import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

type PusherConnector = {
  pusher?: {
    config?: {
      auth?: {
        headers?: Record<string, string>
      }
    }
  }
  options?: {
    auth?: {
      headers?: Record<string, string>
    }
    authEndpoint?: string
  }
}

let echoInstance: Echo<'reverb'> | null = null

export function getEcho(): Echo<'reverb'> {
  if (echoInstance) {
    return echoInstance
  }

  const token = localStorage.getItem('auth.access_token')
  const apiBase = import.meta.env.VITE_API_URL ?? '/api/v1'
  const apiOrigin = apiBase.startsWith('http') ? new URL(apiBase).origin : window.location.origin
  const authEndpoint = import.meta.env.VITE_REVERB_AUTH_ENDPOINT ?? `${apiOrigin}/broadcasting/auth`
  const rawWsHost = import.meta.env.VITE_REVERB_HOST
  const scheme = import.meta.env.VITE_REVERB_SCHEME ?? 'http'
  const forceTLS = scheme === 'https'
  const defaultPort = forceTLS ? 443 : 8080

  let wsHost = rawWsHost
  let wsPort = Number(import.meta.env.VITE_REVERB_PORT ?? defaultPort)
  let wssPort = Number(import.meta.env.VITE_REVERB_PORT ?? defaultPort)

  if (rawWsHost?.includes(':')) {
    const [host, port] = rawWsHost.split(':')
    wsHost = host
    const parsedPort = Number(port)
    if (!Number.isNaN(parsedPort) && parsedPort > 0) {
      wsPort = parsedPort
      wssPort = parsedPort
    }
  }

  ;(window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost,
    wsPort,
    wssPort,
    forceTLS,
    enabledTransports: ['ws', 'wss'],
    authEndpoint,
    auth: {
      headers: {
        Authorization: token ? `Bearer ${token}` : '',
      },
    },
  })

  return echoInstance
}

export function setEchoAuthToken(token: string | null) {
  const echo = getEcho()
  const headerValue = token ? `Bearer ${token}` : ''

  echo.options.auth = {
    headers: {
      Authorization: headerValue,
    },
  }

  const connector = echo.connector as PusherConnector
  if (connector?.pusher?.config?.auth?.headers) {
    connector.pusher.config.auth.headers.Authorization = headerValue
  }
  if (connector?.options?.auth?.headers) {
    connector.options.auth.headers.Authorization = headerValue
  }
}
