import { ref, computed, watch } from 'vue'

export type Theme = 'light' | 'dark' | 'system'
export type ResolvedTheme = 'light' | 'dark'

const STORAGE_KEY = 'fakebnb-theme'

// État global partagé entre toutes les instances
const theme = ref<Theme>('system')
const resolvedTheme = ref<ResolvedTheme>('light')
const systemPreference = ref<ResolvedTheme>('light')

// Media query pour détecter la préférence système
let mediaQuery: MediaQueryList | null = null

/**
 * Met à jour la préférence système détectée
 */
function updateSystemPreference() {
  if (typeof window === 'undefined') return

  const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches
  systemPreference.value = prefersDark ? 'dark' : 'light'
}

/**
 * Résout le thème effectif en fonction du choix utilisateur
 */
function resolveTheme(themeValue: Theme): ResolvedTheme {
  if (themeValue === 'system') {
    return systemPreference.value
  }
  return themeValue
}

/**
 * Applique le thème résolu au DOM
 */
function applyTheme(resolved: ResolvedTheme) {
  if (typeof document === 'undefined') return

  const html = document.documentElement

  if (resolved === 'dark') {
    html.classList.add('dark')
    html.style.colorScheme = 'dark'
  } else {
    html.classList.remove('dark')
    html.style.colorScheme = 'light'
  }
}

/**
 * Charge le thème depuis localStorage
 */
function loadTheme(): Theme {
  if (typeof window === 'undefined') return 'system'

  const stored = localStorage.getItem(STORAGE_KEY)

  if (stored === 'light' || stored === 'dark' || stored === 'system') {
    return stored
  }

  return 'system'
}

/**
 * Sauvegarde le thème dans localStorage
 */
function saveTheme(themeValue: Theme) {
  if (typeof window === 'undefined') return
  localStorage.setItem(STORAGE_KEY, themeValue)
}

/**
 * Initialise le système de thème
 */
function initTheme() {
  if (typeof window === 'undefined') return

  // Détecter la préférence système
  updateSystemPreference()

  // Écouter les changements de préférence système
  mediaQuery = window.matchMedia('(prefers-color-scheme: dark)')
  mediaQuery.addEventListener('change', updateSystemPreference)

  // Charger le thème sauvegardé
  theme.value = loadTheme()

  // Résoudre et appliquer
  resolvedTheme.value = resolveTheme(theme.value)
  applyTheme(resolvedTheme.value)
}

/**
 * Nettoie les event listeners
 */
function cleanup() {
  if (mediaQuery) {
    mediaQuery.removeEventListener('change', updateSystemPreference)
  }
}

// Watchers pour réactivité
watch(theme, (newTheme) => {
  saveTheme(newTheme)
  resolvedTheme.value = resolveTheme(newTheme)
})

watch(systemPreference, () => {
  if (theme.value === 'system') {
    resolvedTheme.value = resolveTheme(theme.value)
  }
})

watch(resolvedTheme, (newResolved) => {
  applyTheme(newResolved)
})

// Auto-initialisation au chargement du module (SSR-safe)
if (typeof window !== 'undefined') {
  initTheme()
}

/**
 * Composable principal pour gérer le thème
 */
export function useTheme() {
  // Computed pour savoir si on est en mode sombre
  const isDark = computed(() => resolvedTheme.value === 'dark')
  const isLight = computed(() => resolvedTheme.value === 'light')
  const isSystem = computed(() => theme.value === 'system')

  /**
   * Change le thème
   */
  function setTheme(newTheme: Theme) {
    theme.value = newTheme
  }

  /**
   * Toggle entre light et dark (désactive le mode system)
   */
  function toggleTheme() {
    if (resolvedTheme.value === 'dark') {
      theme.value = 'light'
    } else {
      theme.value = 'dark'
    }
  }

  /**
   * Cycle entre light → dark → system
   */
  function cycleTheme() {
    if (theme.value === 'light') {
      theme.value = 'dark'
    } else if (theme.value === 'dark') {
      theme.value = 'system'
    } else {
      theme.value = 'light'
    }
  }

  return {
    // État
    theme: computed(() => theme.value),
    resolvedTheme: computed(() => resolvedTheme.value),
    systemPreference: computed(() => systemPreference.value),

    // Computed helpers
    isDark,
    isLight,
    isSystem,

    // Actions
    setTheme,
    toggleTheme,
    cycleTheme,

    // Cleanup (utile pour tests)
    cleanup,
  }
}
