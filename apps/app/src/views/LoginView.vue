<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { startOAuthFlow } from '@/services/oauth'

const route = useRoute()
const error = ref<string | null>(null)
const isLoading = ref(false)

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
</script>

<template>
  <section class="mx-auto max-w-md space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <header class="space-y-1">
      <Breadcrumbs :items="[{ label: 'Accueil', to: '/' }, { label: 'Connexion' }]" />
      <h1 class="text-2xl font-semibold text-slate-900">Connexion</h1>
      <p class="text-sm text-slate-500">Vous allez être redirigé vers Fakebnb SSO.</p>
    </header>

    <div class="space-y-4">
      <p v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
        {{ error }}
      </p>

      <button
        class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="isLoading"
        type="button"
        @click="submit"
      >
        {{ isLoading ? 'Redirection...' : 'Continuer avec Fakebnb' }}
      </button>
    </div>
  </section>
</template>
