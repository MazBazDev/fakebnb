<script setup lang="ts">
import { computed, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { becomeHost } from '@/services/me'

const auth = useAuthStore()
const displayName = computed(() => auth.user?.name ?? auth.user?.email ?? 'Utilisateur')
const isSubmitting = ref(false)
const feedback = ref<string | null>(null)
const error = ref<string | null>(null)

async function activateHost() {
  feedback.value = null
  error.value = null
  isSubmitting.value = true

  try {
    const response = await becomeHost()
    feedback.value = response.message
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible d’activer le rôle hôte.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="space-y-6">
    <header class="space-y-2">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Espace privé</p>
      <h1 class="text-3xl font-semibold text-slate-900">Bonjour {{ displayName }}</h1>
      <p class="text-sm text-slate-500">
        Ce tableau de bord sera enrichi avec les annonces, réservations et la messagerie.
      </p>
    </header>

    <div class="grid gap-4 md:grid-cols-2">
      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <p class="text-sm font-semibold text-slate-700">Rôle</p>
        <p class="text-sm text-slate-500">Client (par défaut)</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <p class="text-sm font-semibold text-slate-700">Actions rapides</p>
        <p class="text-sm text-slate-500">Devenir hôte, créer une annonce, gérer les co-hôtes.</p>
        <button
          class="mt-3 inline-flex rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="isSubmitting"
          type="button"
          @click="activateHost"
        >
          {{ isSubmitting ? 'Activation...' : 'Devenir hôte' }}
        </button>
      </div>
    </div>

    <p v-if="feedback" class="rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-600">
      {{ feedback }}
    </p>
    <p v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ error }}
    </p>

    <div class="rounded-2xl border border-slate-200 bg-white p-5">
      <h2 class="text-sm font-semibold text-slate-700">Gestion des co-hôtes</h2>
      <p class="mt-2 text-sm text-slate-500">
        Accède à la liste et aux permissions des co-hôtes.
      </p>
      <RouterLink
        to="/dashboard/cohosts"
        class="mt-4 inline-flex rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white"
      >
        Ouvrir
      </RouterLink>
    </div>
  </section>
</template>
