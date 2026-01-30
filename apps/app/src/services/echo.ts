import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

let echoInstance: Echo | null = null

export function getEcho() {
  if (echoInstance) {
    return echoInstance
  }

  const token = localStorage.getItem('auth.token')

  ;(window as unknown as { Pusher: typeof Pusher }).Pusher = Pusher

  echoInstance = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: Number(import.meta.env.VITE_REVERB_PORT ?? 8080),
    wssPort: Number(import.meta.env.VITE_REVERB_PORT ?? 443),
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: import.meta.env.VITE_REVERB_AUTH_ENDPOINT,
    auth: {
      headers: {
        Authorization: token ? `Bearer ${token}` : '',
      },
    },
  })

  return echoInstance
}
