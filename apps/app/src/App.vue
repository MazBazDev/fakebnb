<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, RouterView } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { gravatarUrl } from '@/utils/gravatar'

const auth = useAuthStore()
const isAuthed = computed(() => auth.isAuthenticated)
const dropdownOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)

const avatarUrl = computed(() => {
  if (!auth.user) return null
  if (auth.user.profile_photo_url) return auth.user.profile_photo_url
  if (auth.user.email) return gravatarUrl(auth.user.email, 64)
  return null
})

const initials = computed(() => {
  if (!auth.user?.name) return 'MB'
  return auth.user.name
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0]?.toUpperCase())
    .slice(0, 2)
    .join('')
})

onMounted(async () => {
  if (auth.token && !auth.user) {
    try {
      await auth.fetchMe()
    } catch {
      auth.setSession(null, null)
    }
  }
})

function toggleDropdown() {
  dropdownOpen.value = !dropdownOpen.value
}

function handleClickOutside(event: MouseEvent) {
  if (!dropdownRef.value) return
  if (!dropdownRef.value.contains(event.target as Node)) {
    dropdownOpen.value = false
  }
}

onMounted(() => {
  document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
  document.removeEventListener('click', handleClickOutside)
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
          <div v-if="isAuthed" ref="dropdownRef" class="relative">
            <button
              class="flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700"
              type="button"
              @click="toggleDropdown"
            >
              <span
                class="flex h-8 w-8 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-xs font-semibold text-slate-700"
              >
                <img v-if="avatarUrl" :src="avatarUrl" class="h-full w-full object-cover" />
                <span v-else>{{ initials }}</span>
              </span>
              <span class="hidden sm:inline">{{ auth.user?.name }}</span>
            </button>

            <div
              v-if="dropdownOpen"
              class="absolute right-0 mt-2 w-48 rounded-2xl border border-slate-200 bg-white p-2 text-xs shadow-lg"
            >
              <RouterLink
                to="/profile"
                class="block rounded-xl px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900"
              >
                Profil
              </RouterLink>
              <RouterLink
                to="/bookings"
                class="block rounded-xl px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900"
              >
                Historique
              </RouterLink>
              <RouterLink
                to="/messages"
                class="block rounded-xl px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900"
              >
                Mes messages
              </RouterLink>
              <button
                class="mt-1 w-full rounded-xl px-3 py-2 text-left text-rose-600 hover:bg-rose-50"
                type="button"
                @click="auth.logout"
              >
                Déconnexion
              </button>
            </div>
          </div>
        </nav>
      </div>
    </header>

    <main class="mx-auto max-w-6xl px-6 py-10">
      <RouterView />
    </main>
  </div>
</template>
