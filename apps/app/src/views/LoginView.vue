<script setup lang="ts">
import { ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { startOAuthFlow } from '@/services/oauth'
import { apiFetch } from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { PageHeader, AlertMessage } from '@/components/ui'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const error = ref<string | null>(null)
const isLoading = ref(false)
const isQuickLoading = ref<string | null>(null)
const showQuickLogin = true

type DevLoginResponse = {
  access_token: string
  refresh_token?: string | null
  expires_in?: number
  user: {
    id: number
    name: string
    email: string
    address?: string | null
    profile_photo_url?: string | null
  }
}

async function submit() {
  error.value = null
  isLoading.value = true

  try {
    const redirect = (route.query.redirect as string | undefined) ?? '/listings'
    await startOAuthFlow(redirect)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Connexion impossible.'
    isLoading.value = false
  }
}

async function quickLogin(role: 'client' | 'host' | 'cohost') {
  error.value = null
  isQuickLoading.value = role

  try {
    const response = await apiFetch<DevLoginResponse>('/auth/dev-login', {
      method: 'POST',
      body: JSON.stringify({ role }),
    })

    const expiresAt = response.expires_in
      ? new Date(Date.now() + response.expires_in * 1000).toISOString()
      : null

    auth.setSession(response.access_token, response.refresh_token ?? null, expiresAt, response.user)

    const redirect = (route.query.redirect as string | undefined) ?? '/listings'
    await router.push(redirect)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Connexion impossible.'
  } finally {
    isQuickLoading.value = null
  }
}
</script>

<template>
  <section class="mx-auto max-w-md space-y-8">
    <PageHeader
      title="Connexion"
      subtitle="Vous allez être redirigé vers Fakebnb SSO"
      :breadcrumbs="[{ label: 'Accueil', to: '/' }, { label: 'Connexion' }]"
    />

    <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
      <AlertMessage v-if="error" :message="error" type="error" class="mb-6" />

      <div v-if="showQuickLogin" class="mb-6 space-y-3">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">
          Se connecter en tant que
        </p>
        <div class="grid gap-3 sm:grid-cols-3">
          <button
            type="button"
            class="rounded-lg border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-[#FF385C] hover:text-[#FF385C] disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isQuickLoading !== null"
            @click="quickLogin('client')"
          >
            {{ isQuickLoading === 'client' ? 'Connexion...' : 'Client' }}
          </button>
          <button
            type="button"
            class="rounded-lg border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-[#FF385C] hover:text-[#FF385C] disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isQuickLoading !== null"
            @click="quickLogin('host')"
          >
            {{ isQuickLoading === 'host' ? 'Connexion...' : 'Host' }}
          </button>
          <button
            type="button"
            class="rounded-lg border border-gray-200 px-4 py-3 text-sm font-semibold text-gray-700 transition hover:border-[#FF385C] hover:text-[#FF385C] disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="isQuickLoading !== null"
            @click="quickLogin('cohost')"
          >
            {{ isQuickLoading === 'cohost' ? 'Connexion...' : 'Co-host' }}
          </button>
        </div>
        <div class="border-t border-gray-100 pt-4"></div>
      </div>

      <button
        class="w-full rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="isLoading"
        type="button"
        @click="submit"
      >
        <span v-if="isLoading" class="inline-flex items-center gap-2">
          <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          Redirection en cours...
        </span>
        <span v-else class="inline-flex items-center gap-2">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"
            />
          </svg>
          Continuer avec Fakebnb
        </span>
      </button>

      <div class="mt-6 border-t border-gray-100 pt-6 text-center">
        <p class="text-sm text-gray-600">
          Pas encore de compte ?
          <RouterLink
            to="/register"
            class="font-semibold text-[#222222] underline transition hover:text-[#E61E4D]"
          >
            Inscrivez-vous
          </RouterLink>
        </p>
      </div>
    </div>
  </section>
</template>
