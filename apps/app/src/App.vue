<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { gravatarUrl } from '@/utils/gravatar'

const auth = useAuthStore()
const route = useRoute()
const isAuthed = computed(() => auth.isAuthenticated)
const isHostRoute = computed(() => route.meta.layout === 'host')
const dropdownOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)
const sidebarOpen = ref(false)

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

const showSidebar = computed(() => {
  return isAuthed.value && isHostRoute.value
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
    <div class="flex min-h-screen">
      <aside
        v-if="showSidebar"
        class="hidden w-64 flex-col border-r border-slate-200 bg-white px-4 py-6 md:flex"
      >
        <RouterLink to="/" class="text-sm font-semibold tracking-wide text-slate-900">
          MiniBnB
        </RouterLink>

        <nav class="mt-8 flex flex-1 flex-col gap-2 text-sm text-slate-600">
          <RouterLink to="/host" class="rounded-xl px-3 py-2 hover:bg-slate-50">
            Tableau de bord
          </RouterLink>
          <RouterLink to="/host/listings" class="rounded-xl px-3 py-2 hover:bg-slate-50">
            Mes annonces
          </RouterLink>
          <RouterLink to="/host/bookings" class="rounded-xl px-3 py-2 hover:bg-slate-50">
            Réservations reçues
          </RouterLink>
          <RouterLink to="/host/cohosts" class="rounded-xl px-3 py-2 hover:bg-slate-50">
            Co-hôtes
          </RouterLink>
          <RouterLink to="/listings" class="rounded-xl px-3 py-2 hover:bg-slate-50">
            Retour au mode voyageur
          </RouterLink>
        </nav>

        <div class="mt-6 border-t border-slate-200 pt-4">
          <div class="flex items-center gap-3">
            <span
              class="flex h-10 w-10 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-xs font-semibold text-slate-700"
            >
              <img v-if="avatarUrl" :src="avatarUrl" class="h-full w-full object-cover" />
              <span v-else>{{ initials }}</span>
            </span>
            <div class="text-xs">
              <p class="font-semibold text-slate-800">{{ auth.user?.name }}</p>
              <p class="text-slate-400">{{ auth.user?.email }}</p>
            </div>
          </div>
          <button
            class="mt-4 w-full rounded-xl border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-700"
            type="button"
            @click="auth.logout"
          >
            Déconnexion
          </button>
        </div>
      </aside>

      <div class="flex flex-1 flex-col">
        <header
          v-if="showSidebar"
          class="flex items-center justify-between border-b border-slate-200 bg-white px-6 py-4 md:hidden"
        >
          <RouterLink to="/" class="text-sm font-semibold tracking-wide text-slate-900">
            MiniBnB
          </RouterLink>
          <button
            class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold"
            type="button"
            @click="sidebarOpen = true"
          >
            Menu
          </button>
        </header>

        <header v-else class="border-b border-slate-200 bg-white/80 backdrop-blur">
          <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
            <RouterLink to="/" class="text-sm font-semibold tracking-wide text-slate-900">
              MiniBnB
            </RouterLink>

            <nav class="flex items-center gap-4 text-sm">
              <RouterLink to="/" class="text-slate-600 hover:text-slate-900">Accueil</RouterLink>
              <RouterLink to="/listings" class="text-slate-600 hover:text-slate-900">
                Annonces
              </RouterLink>
              <RouterLink
                v-if="isAuthed"
                to="/host"
                class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
              >
                Mode hôte
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
                    to="/host"
                    class="block rounded-xl px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900"
                  >
                    Mode hôte
                  </RouterLink>
                  <RouterLink
                    to="/bookings"
                    class="block rounded-xl px-3 py-2 text-slate-600 hover:bg-slate-50 hover:text-slate-900"
                  >
                    Historique
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

        <main class="flex-1 px-6 py-10">
          <RouterView />
        </main>
      </div>
    </div>

    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-50 flex md:hidden"
      role="dialog"
      aria-modal="true"
    >
      <div class="absolute inset-0 bg-slate-900/30" @click="sidebarOpen = false"></div>
      <aside class="relative h-full w-72 bg-white p-6 shadow-xl">
        <button
          class="mb-6 rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold"
          type="button"
          @click="sidebarOpen = false"
        >
          Fermer
        </button>
        <nav class="flex flex-col gap-2 text-sm text-slate-600">
          <RouterLink to="/host" class="rounded-xl px-3 py-2 hover:bg-slate-50">
            Tableau de bord
          </RouterLink>
          <RouterLink to="/host/listings" class="rounded-xl px-3 py-2 hover:bg-slate-50">
            Mes annonces
          </RouterLink>
          <RouterLink to="/host/listings/new" class="rounded-xl px-3 py-2 hover:bg-slate-50">
            Nouvelle annonce
          </RouterLink>
          <RouterLink to="/host/bookings" class="rounded-xl px-3 py-2 hover:bg-slate-50">
            Réservations reçues
          </RouterLink>
          <RouterLink to="/host/cohosts" class="rounded-xl px-3 py-2 hover:bg-slate-50">
            Co-hôtes
          </RouterLink>
          <RouterLink to="/listings" class="rounded-xl px-3 py-2 hover:bg-slate-50">
            Retour au mode voyageur
          </RouterLink>
        </nav>
      </aside>
    </div>
  </div>
</template>
