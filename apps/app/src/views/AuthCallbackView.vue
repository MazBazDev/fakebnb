<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { exchangeAuthorizationCode } from '@/services/oauth'
import { AlertMessage, LoadingSpinner } from '@/components/ui'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()

const error = ref<string | null>(null)
const isProcessing = ref(true)

onMounted(async () => {
  const code = route.query.code as string | undefined
  const state = route.query.state as string | undefined

  if (!code || !state) {
    error.value = 'Réponse OAuth invalide.'
    isProcessing.value = false
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
    isProcessing.value = false
  }
})
</script>

<template>
  <section class="mx-auto max-w-md space-y-6 rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
    <div class="text-center">
      <h1 class="text-2xl font-semibold text-[#222222]">Connexion en cours</h1>
      <p class="mt-2 text-sm text-gray-600">Nous finalisons votre session</p>
    </div>

    <AlertMessage v-if="error" :message="error" type="error" />

    <LoadingSpinner v-if="isProcessing && !error" text="Authentification..." />

    <div v-if="error" class="text-center">
      <RouterLink
        to="/login"
        class="inline-flex items-center gap-2 text-sm font-semibold text-[#222222] underline transition hover:text-[#E61E4D]"
      >
        Réessayer la connexion
      </RouterLink>
    </div>
  </section>
</template>
