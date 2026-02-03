<script setup lang="ts">
import { useTheme } from '@/composables/useTheme'

const { isDark, toggleTheme } = useTheme()
</script>

<template>
  <button
    type="button"
    class="theme-toggle-btn group relative flex h-10 w-10 items-center justify-center rounded-full transition-all duration-300 hover:scale-110 active:scale-95"
    :class="[
      isDark
        ? 'bg-slate-700/80 hover:bg-slate-600/90'
        : 'bg-gray-100 hover:bg-gray-200'
    ]"
    :aria-label="isDark ? 'Passer au thème clair' : 'Passer au thème sombre'"
    :aria-pressed="isDark"
    @click="toggleTheme"
  >
    <!-- Sun Icon (Light Mode) -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 rotate-90 scale-0"
      enter-to-class="opacity-100 rotate-0 scale-100"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="opacity-100 rotate-0 scale-100"
      leave-to-class="opacity-0 -rotate-90 scale-0"
    >
      <svg
        v-if="!isDark"
        class="absolute h-5 w-5 text-amber-500 drop-shadow-sm"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="2.5"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
        />
      </svg>
    </Transition>

    <!-- Moon Icon (Dark Mode) -->
    <Transition
      enter-active-class="transition-all duration-300 ease-out"
      enter-from-class="opacity-0 -rotate-90 scale-0"
      enter-to-class="opacity-100 rotate-0 scale-100"
      leave-active-class="transition-all duration-300 ease-in"
      leave-from-class="opacity-100 rotate-0 scale-100"
      leave-to-class="opacity-0 rotate-90 scale-0"
    >
      <svg
        v-if="isDark"
        class="absolute h-5 w-5 text-slate-100 drop-shadow-sm"
        fill="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          d="M21.64,13a1,1,0,0,0-1.05-.14,8.05,8.05,0,0,1-3.37.73A8.15,8.15,0,0,1,9.08,5.49a8.59,8.59,0,0,1,.25-2A1,1,0,0,0,8,2.36,10.14,10.14,0,1,0,22,14.05,1,1,0,0,0,21.64,13Zm-9.5,6.69A8.14,8.14,0,0,1,7.08,5.22v.27A10.15,10.15,0,0,0,17.22,15.63a9.79,9.79,0,0,0,2.1-.22A8.11,8.11,0,0,1,12.14,19.73Z"
        />
      </svg>
    </Transition>

    <!-- Ripple effect on click -->
    <span
      class="pointer-events-none absolute inset-0 rounded-full opacity-0 transition-opacity duration-200 group-active:opacity-20"
      :class="[
        isDark
          ? 'bg-white'
          : 'bg-black'
      ]"
    ></span>
  </button>
</template>

<style scoped>
.theme-toggle-btn {
  box-shadow: var(--shadow-sm);
  backdrop-filter: blur(8px);
}

.theme-toggle-btn:hover {
  box-shadow: var(--shadow-md);
}

.theme-toggle-btn:active {
  box-shadow: var(--shadow-sm);
}
</style>
