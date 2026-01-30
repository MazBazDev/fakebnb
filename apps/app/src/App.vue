<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const isAuthed = computed(() => auth.isAuthenticated)

onMounted(async () => {
  if (auth.token && !auth.user) {
    try {
      await auth.fetchMe()
    } catch {
      auth.setSession(null, null)
    }
  }
})
</script>

<template>
  <div class="min-h-screen bg-slate-50 text-slate-900">
    <header class="border-b border-slate-200 bg-white/80 backdrop-blur">
      <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
        <RouterLink to="/" class="text-sm font-semibold tracking-wide text-slate-900">
          MiniBnB
        </RouterLink>

        <nav class="flex items-center gap-4 text-sm">
          <RouterLink to="/" class="text-slate-600 hover:text-slate-900">Accueil</RouterLink>
          <RouterLink to="/listings" class="text-slate-600 hover:text-slate-900">
            Annonces
          </RouterLink>
          <RouterLink to="/bookings" class="text-slate-600 hover:text-slate-900">
            Réservations
          </RouterLink>
          <RouterLink to="/messages" class="text-slate-600 hover:text-slate-900">
            Messages
          </RouterLink>
          <RouterLink to="/profile" class="text-slate-600 hover:text-slate-900">
            Profil
          </RouterLink>
          <RouterLink to="/dashboard" class="text-slate-600 hover:text-slate-900">
            Dashboard
          </RouterLink>
          <RouterLink v-if="!isAuthed" to="/login" class="text-slate-600 hover:text-slate-900">
            Connexion
          </RouterLink>
          <RouterLink
            v-if="!isAuthed"
            to="/register"
            class="rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white"
          >
            S’inscrire
          </RouterLink>
          <button
            v-if="isAuthed"
            class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
            type="button"
            @click="auth.logout"
          >
            Déconnexion
          </button>
        </nav>
      </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-10">
      <RouterView />
    </main>
  </div>
</template>
