<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const auth = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const error = ref<string | null>(null)
const isLoading = ref(false)

async function submit() {
  error.value = null
  isLoading.value = true

  try {
    await auth.register({ name: name.value, email: email.value, password: password.value })
    await router.replace('/listings')
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Inscription impossible.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <section class="mx-auto max-w-md space-y-6 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
    <header class="space-y-1">
      <Breadcrumbs :items="[{ label: 'Accueil', to: '/' }, { label: 'Inscription' }]" />
      <h1 class="text-2xl font-semibold text-slate-900">Créer un compte</h1>
      <p class="text-sm text-slate-500">Commencez par un profil client.</p>
    </header>

    <form class="space-y-4" @submit.prevent="submit">
      <div class="space-y-2">
        <label class="text-sm font-medium text-slate-700">Nom</label>
        <input
          v-model="name"
          type="text"
          autocomplete="name"
          class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
          required
        />
      </div>
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
          autocomplete="new-password"
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
        {{ isLoading ? 'Création...' : 'Créer le compte' }}
      </button>
    </form>
  </section>
</template>
