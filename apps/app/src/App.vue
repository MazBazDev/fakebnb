<script setup lang="ts">
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { useNotificationsStore } from '@/stores/notifications'
import { gravatarUrl } from '@/utils/gravatar'
import NotificationBell from '@/components/NotificationBell.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
import ClickSpark from '@/components/ClickSpark.vue'
import { startOAuthFlow } from '@/services/oauth'

const auth = useAuthStore()
const notifications = useNotificationsStore()
const route = useRoute()
const isAuthed = computed(() => auth.isAuthenticated)
const isHostRoute = computed(() => route.meta.layout === 'host')
const dropdownOpen = ref(false)
const dropdownRef = ref<HTMLElement | null>(null)
const sidebarOpen = ref(false)
let notificationInterval: number | null = null
const isLoggingOut = ref(false)
const apiOrigin = computed(() => {
  const apiBase = import.meta.env.VITE_API_URL ?? '/api/v1'
  if (apiBase.startsWith('http')) {
    return new URL(apiBase).origin
  }
  return window.location.origin
})
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
      auth.setSession(null, null, null, null)
    }
  }
})

function toggleDropdown() {
  dropdownOpen.value = !dropdownOpen.value
}

async function redirectToOAuth(redirectTo = '/listings') {
  await startOAuthFlow(redirectTo)
}

async function handleLogout() {
  if (isLoggingOut.value) return
  isLoggingOut.value = true

  try {
    await auth.logout()
    window.location.href = `${apiOrigin.value}/logout?redirect=${encodeURIComponent(window.location.origin)}`
  } finally {
    isLoggingOut.value = false
  }
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
  if (notificationInterval) {
    window.clearInterval(notificationInterval)
    notificationInterval = null
  }
})

watch(
  () => auth.user?.id,
  (userId) => {
    if (!userId) {
      if (notificationInterval) {
        window.clearInterval(notificationInterval)
        notificationInterval = null
      }
      notifications.clear()
      return
    }
    notifications.bindToUser(userId)
    notifications.refreshUnreadCount()
    notifications.hydrate()
    if (!notificationInterval) {
      notificationInterval = window.setInterval(() => {
        notifications.refreshUnreadCount()
      }, 20000)
    }
  },
  { immediate: true }
)
</script>

<template>
  <div class="app-container">
    <div class="flex min-h-screen">
      <!-- Sidebar Host Mode -->
      <aside
        v-if="showSidebar"
        class="sidebar hidden md:flex"
      >
        <ClickSpark :count="12" :colors="['#FF385C', '#E61E4D', '#ffd700', '#ff6b6b']" :size="6">
          <RouterLink to="/" class="text-xl font-semibold tracking-tight text-[#FF385C]">
            Fakebnb
          </RouterLink>
        </ClickSpark>

        <nav class="mt-12 flex flex-1 flex-col gap-1">
          <RouterLink
            to="/host"
            class="nav-link"
          >
            Tableau de bord
          </RouterLink>
          <RouterLink
            to="/host/listings"
            class="nav-link"
          >
            Annonces
          </RouterLink>
          <RouterLink
            to="/host/bookings"
            class="nav-link"
          >
            Réservations
          </RouterLink>
          <RouterLink
            to="/host/cohosts"
            class="nav-link"
          >
            Co-hôtes
          </RouterLink>
        </nav>

        <div class="sidebar-footer">
          <div class="flex items-center gap-3">
            <span
              class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-700 text-xs font-semibold text-white"
            >
              <img v-if="avatarUrl" :src="avatarUrl" class="h-full w-full object-cover" />
              <span v-else>{{ initials }}</span>
            </span>
            <div class="min-w-0 flex-1 text-sm">
              <p class="truncate font-semibold text-primary">{{ auth.user?.name }}</p>
              <p class="truncate text-xs text-secondary">{{ auth.user?.email }}</p>
            </div>
          </div>
          <button
            class="btn-outline w-full"
            type="button"
            :disabled="isLoggingOut"
            @click="handleLogout"
          >
            {{ isLoggingOut ? 'Déconnexion...' : 'Déconnexion' }}
          </button>
        </div>
      </aside>

      <div class="flex flex-1 flex-col">
        <!-- Header -->
        <header class="header">
          <div class="mx-auto flex max-w-[2520px] items-center justify-between px-6 py-4 lg:px-20">
            <div class="flex items-center gap-8">
              <ClickSpark v-if="!showSidebar" :count="12" :colors="['#FF385C', '#E61E4D', '#ffd700', '#ff6b6b']" :size="6">
                <RouterLink
                  to="/"
                  class="text-2xl font-semibold tracking-tight text-[#FF385C]"
                >
                  Fakebnb
                </RouterLink>
              </ClickSpark>
              <button
                v-if="showSidebar"
                class="mobile-menu-btn md:hidden"
                type="button"
                @click="sidebarOpen = true"
              >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M4 6h16M4 12h16M4 18h16"
                  />
                </svg>
                Menu
              </button>
            </div>

            <nav class="flex items-center gap-4">
              <ThemeToggle />
              <NotificationBell v-if="isAuthed" />
              <RouterLink
                v-if="isAuthed"
                :to="showSidebar ? '/listings' : '/host'"
                class="nav-link-header hidden sm:block"
              >
                {{ showSidebar ? 'Voyageur' : 'Devenir hôte' }}
              </RouterLink>
              <button
                v-if="!isAuthed"
                class="nav-link-header"
                type="button"
                @click="redirectToOAuth()"
              >
                Connexion
              </button>
              <ClickSpark v-if="!isAuthed" :count="15" :colors="['#ffd700', '#ff6b6b', '#4ecdc4', '#FF385C']" :size="8">
                <button
                  class="btn-primary"
                  type="button"
                  @click="redirectToOAuth()"
                >
                  S'inscrire
                </button>
              </ClickSpark>
              <div v-if="isAuthed" ref="dropdownRef" class="relative">
                <button
                  class="user-menu-btn"
                  type="button"
                  @click="toggleDropdown"
                >
                  <svg class="h-4 w-4 text-secondary" fill="none" viewBox="0 0 24 24">
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-width="2"
                      d="M4 6h16M4 12h16M4 18h16"
                    />
                  </svg>
                  <span
                    class="flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-700 text-xs font-semibold text-white"
                  >
                    <img v-if="avatarUrl" :src="avatarUrl" class="h-full w-full object-cover" />
                    <span v-else>{{ initials }}</span>
                  </span>
                </button>

                <div
                  v-if="dropdownOpen"
                  class="dropdown-menu"
                >
                  <div class="py-2">
                    <RouterLink
                      to="/profile"
                      class="dropdown-item"
                    >
                      Profil
                    </RouterLink>
                    <RouterLink
                      to="/messages"
                      class="dropdown-item"
                    >
                      Messages
                    </RouterLink>
                    <RouterLink
                      to="/bookings"
                      class="dropdown-item"
                    >
                      Voyages
                    </RouterLink>
                  </div>
                  <div class="dropdown-divider">
                    <button
                      class="dropdown-item w-full text-left"
                      type="button"
                      :disabled="isLoggingOut"
                      @click="handleLogout"
                    >
                      {{ isLoggingOut ? 'Déconnexion...' : 'Déconnexion' }}
                    </button>
                  </div>
                </div>
              </div>
            </nav>
          </div>
        </header>

        <!-- Main Content -->
        <main class="main-content">
          <div class="mx-auto max-w-[2520px] px-6 py-8 lg:px-20 lg:py-12">
            <RouterView :key="route.fullPath" />
          </div>
        </main>
      </div>
    </div>

    <!-- Mobile Sidebar Overlay -->
    <div
      v-if="sidebarOpen"
      class="fixed inset-0 z-50 flex md:hidden"
      role="dialog"
      aria-modal="true"
    >
      <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="sidebarOpen = false"></div>
      <aside class="mobile-sidebar">
        <div class="mb-8 flex items-center justify-between">
          <ClickSpark :count="12" :colors="['#FF385C', '#E61E4D', '#ffd700', '#ff6b6b']" :size="6">
            <RouterLink to="/" class="text-xl font-semibold tracking-tight text-[#FF385C]">
              Fakebnb
            </RouterLink>
          </ClickSpark>
          <button
            class="close-btn"
            type="button"
            @click="sidebarOpen = false"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
        <nav class="flex flex-col gap-1">
          <RouterLink
            to="/host"
            class="nav-link"
            @click="sidebarOpen = false"
          >
            Tableau de bord
          </RouterLink>
          <RouterLink
            to="/host/listings"
            class="nav-link"
            @click="sidebarOpen = false"
          >
            Annonces
          </RouterLink>
          <RouterLink
            to="/host/listings/new"
            class="nav-link"
            @click="sidebarOpen = false"
          >
            Créer une annonce
          </RouterLink>
          <RouterLink
            to="/host/bookings"
            class="nav-link"
            @click="sidebarOpen = false"
          >
            Réservations
          </RouterLink>
          <RouterLink
            to="/host/cohosts"
            class="nav-link"
            @click="sidebarOpen = false"
          >
            Co-hôtes
          </RouterLink>
        </nav>
      </aside>
    </div>
  </div>
</template>

<style scoped>
/* Container principal */
.app-container {
  min-height: 100vh;
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  transition: background-color var(--transition-base), color var(--transition-base);
}

/* Sidebar */
.sidebar {
  position: sticky;
  top: 0;
  height: 100vh;
  width: 16rem;
  flex-direction: column;
  border-right: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  padding: 2rem 1.5rem;
  overflow-y: auto;
  transition: background-color var(--transition-base), border-color var(--transition-base);
}

.sidebar-footer {
  margin-top: auto;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border-primary);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

/* Header */
.header {
  position: sticky;
  top: 0;
  z-index: 40;
  border-bottom: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  transition: background-color var(--transition-base), border-color var(--transition-base);
}

/* Main content */
.main-content {
  flex: 1;
  background-color: var(--color-bg-primary);
  transition: background-color var(--transition-base);
}

/* Navigation links */
.nav-link {
  display: block;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.9375rem;
  font-weight: 500;
  color: var(--color-text-secondary);
  transition: background-color var(--transition-fast), color var(--transition-fast);
}

.nav-link:hover {
  background-color: var(--color-bg-hover);
  color: var(--color-text-primary);
}

.nav-link-header {
  padding: 0.625rem 1rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-secondary);
  transition: background-color var(--transition-fast), color var(--transition-fast);
}

.nav-link-header:hover {
  background-color: var(--color-bg-hover);
  color: var(--color-text-primary);
}

/* Buttons */
.btn-primary {
  padding: 0.625rem 1.25rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 600;
  color: white;
  background: linear-gradient(to right, #E61E4D, #D70466);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition-fast), transform var(--transition-fast);
}

.btn-primary:hover {
  box-shadow: var(--shadow-md);
  transform: scale(1.02);
}

.btn-outline {
  padding: 0.625rem 1rem;
  border-radius: 0.5rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-secondary);
  background-color: transparent;
  border: 1px solid var(--color-border-primary);
  transition: all var(--transition-fast);
}

.btn-outline:hover {
  border-color: var(--color-text-primary);
  background-color: var(--color-bg-hover);
  color: var(--color-text-primary);
}

/* User menu button */
.user-menu-btn {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.5rem 0.5rem 0.5rem 1rem;
  border-radius: 9999px;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition-fast);
}

.user-menu-btn:hover {
  box-shadow: var(--shadow-md);
}

/* Mobile menu button */
.mobile-menu-btn {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  padding: 0.5rem 1rem;
  border-radius: 9999px;
  font-size: 0.875rem;
  font-weight: 500;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition-fast);
}

.mobile-menu-btn:hover {
  box-shadow: var(--shadow-md);
}

/* Dropdown menu */
.dropdown-menu {
  position: absolute;
  right: 0;
  margin-top: 0.5rem;
  width: 15rem;
  overflow: hidden;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  box-shadow: var(--shadow-xl);
}

.dropdown-item {
  display: block;
  padding: 0.75rem 1rem;
  font-size: 0.875rem;
  font-weight: 500;
  color: var(--color-text-secondary);
  transition: background-color var(--transition-fast), color var(--transition-fast);
}

.dropdown-item:hover {
  background-color: var(--color-bg-hover);
  color: var(--color-text-primary);
}

.dropdown-divider {
  padding: 0.5rem 0;
  border-top: 1px solid var(--color-border-primary);
}

/* Mobile sidebar */
.mobile-sidebar {
  position: relative;
  height: 100%;
  width: 20rem;
  padding: 2rem 1.5rem;
  background-color: var(--color-bg-primary);
  box-shadow: var(--shadow-xl);
}

.close-btn {
  padding: 0.5rem;
  border-radius: 9999px;
  color: var(--color-text-secondary);
  transition: background-color var(--transition-fast), color var(--transition-fast);
}

.close-btn:hover {
  background-color: var(--color-bg-hover);
  color: var(--color-text-primary);
}

/* Text utilities */
.text-primary {
  color: var(--color-text-primary);
}

.text-secondary {
  color: var(--color-text-secondary);
}
</style>
