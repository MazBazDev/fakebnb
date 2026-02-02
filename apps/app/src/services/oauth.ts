const STORAGE_STATE_KEY = 'oauth.state'
const STORAGE_VERIFIER_KEY = 'oauth.verifier'
const STORAGE_REDIRECT_KEY = 'oauth.redirect'

function base64UrlEncode(buffer: ArrayBuffer): string {
  const bytes = new Uint8Array(buffer)
  let binary = ''
  bytes.forEach((b) => {
    binary += String.fromCharCode(b)
  })
  return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '')
}

function randomString(size = 64): string {
  const bytes = new Uint8Array(size)
  crypto.getRandomValues(bytes)
  return base64UrlEncode(bytes.buffer)
}

async function sha256(input: string): Promise<ArrayBuffer> {
  const data = new TextEncoder().encode(input)
  return crypto.subtle.digest('SHA-256', data)
}

export function getOAuthConfig() {
  const apiBase = import.meta.env.VITE_API_URL ?? '/api/v1'
  const apiOrigin = apiBase.startsWith('http') ? new URL(apiBase).origin : window.location.origin

  return {
    clientId: import.meta.env.VITE_OAUTH_CLIENT_ID as string | undefined,
    authorizeUrl: (import.meta.env.VITE_OAUTH_AUTHORIZE_URL as string | undefined) ?? `${apiOrigin}/oauth/authorize`,
    tokenUrl: (import.meta.env.VITE_OAUTH_TOKEN_URL as string | undefined) ?? `${apiOrigin}/oauth/token`,
    redirectUri:
      (import.meta.env.VITE_OAUTH_REDIRECT_URI as string | undefined) ??
      `${window.location.origin}/auth/callback`,
  }
}

export async function startOAuthFlow(redirectTo?: string) {
  const config = getOAuthConfig()
  if (!config.clientId) {
    throw new Error('OAuth client ID manquant.')
  }

  const state = randomString(32)
  const verifier = randomString(64)
  const challenge = base64UrlEncode(await sha256(verifier))

  sessionStorage.setItem(STORAGE_STATE_KEY, state)
  sessionStorage.setItem(STORAGE_VERIFIER_KEY, verifier)
  if (redirectTo) {
    sessionStorage.setItem(STORAGE_REDIRECT_KEY, redirectTo)
  } else {
    sessionStorage.removeItem(STORAGE_REDIRECT_KEY)
  }

  const params = new URLSearchParams({
    response_type: 'code',
    client_id: config.clientId,
    redirect_uri: config.redirectUri,
    scope: '',
    state,
    code_challenge: challenge,
    code_challenge_method: 'S256',
  })

  window.location.href = `${config.authorizeUrl}?${params.toString()}`
}

export async function exchangeAuthorizationCode(code: string, state: string) {
  const expectedState = sessionStorage.getItem(STORAGE_STATE_KEY)
  const verifier = sessionStorage.getItem(STORAGE_VERIFIER_KEY)
  const redirectTo = sessionStorage.getItem(STORAGE_REDIRECT_KEY)

  sessionStorage.removeItem(STORAGE_STATE_KEY)
  sessionStorage.removeItem(STORAGE_VERIFIER_KEY)
  sessionStorage.removeItem(STORAGE_REDIRECT_KEY)

  if (!expectedState || expectedState !== state) {
    throw new Error('State OAuth invalide.')
  }

  if (!verifier) {
    throw new Error('Code verifier OAuth manquant.')
  }

  const config = getOAuthConfig()
  if (!config.clientId) {
    throw new Error('OAuth client ID manquant.')
  }

  const response = await fetch(config.tokenUrl, {
    method: 'POST',
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      grant_type: 'authorization_code',
      client_id: config.clientId,
      redirect_uri: config.redirectUri,
      code,
      code_verifier: verifier,
    }),
  })

  const payload = await response.json().catch(() => ({}))
  if (!response.ok) {
    throw new Error(payload?.message ?? 'Échange OAuth impossible.')
  }

  return {
    data: payload,
    redirectTo: redirectTo ?? '/listings',
  }
}
