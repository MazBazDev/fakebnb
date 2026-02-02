<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { exchangeAuthorizationCode } from '@/services/oauth'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const error = ref<string | null>(null)

onMounted(async () => {
  const code = route.query.code as string | undefined
  const state = route.query.state as string | undefined

  if (!code || !state) {
    error.value = 'Réponse OAuth invalide.'
    return
  }

  try {
    const { data, redirectTo } = await exchangeAuthorizationCode(code, state)
    const expiresAt = new Date(Date.now() + data.expires_in * 1000).toISOString()

    auth.setSession(data.access_token, data.refresh_token ?? null, expiresAt, null)
    await auth.fetchMe()

    await router.replace(redirectTo)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Connexion impossible.'
  }
})
</script>

<template>
  <section class="mx-auto max-w-md space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <h1 class="text-xl font-semibold text-slate-900">Connexion en cours...</h1>
    <p class="text-sm text-slate-500">Nous finalisons votre session.</p>

    <p v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ error }}
    </p>
  </section>
</template>
