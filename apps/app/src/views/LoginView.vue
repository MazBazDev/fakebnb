<script setup lang="ts">
import { ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const auth = useAuthStore()

const email = ref('')
const password = ref('')
const error = ref<string | null>(null)
const isLoading = ref(false)

async function submit() {
  error.value = null
  isLoading.value = true

  try {
    await auth.login({ email: email.value, password: password.value })
    const redirect = (route.query.redirect as string | undefined) ?? '/dashboard'
    await router.replace(redirect)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Connexion impossible.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <section class="mx-auto max-w-md space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <header class="space-y-1">
      <h1 class="text-2xl font-semibold text-slate-900">Connexion</h1>
      <p class="text-sm text-slate-500">Accédez à votre espace hôte ou client.</p>
    </header>

    <form class="space-y-4" @submit.prevent="submit">
      <div class="space-y-2">
        <label class="text-sm font-medium text-slate-700">Email</label>
        <input
          v-model="email"
          type="email"
          autocomplete="email"
          class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
          required
        />
      </div>
      <div class="space-y-2">
        <label class="text-sm font-medium text-slate-700">Mot de passe</label>
        <input
          v-model="password"
          type="password"
          autocomplete="current-password"
          class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
          required
        />
      </div>

      <p v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
        {{ error }}
      </p>

      <button
        class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="isLoading"
        type="submit"
      >
        {{ isLoading ? 'Connexion...' : 'Se connecter' }}
      </button>
    </form>
  </section>
</template>
