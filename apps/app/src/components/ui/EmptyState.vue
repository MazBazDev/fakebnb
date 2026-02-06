<script setup lang="ts">
import { RouterLink } from 'vue-router'

interface Props {
  /** Main title */
  title: string
  /** Subtitle/description */
  subtitle?: string
  /** Icon name (slot can also be used for custom icons) */
  icon?: 'messages' | 'bookings' | 'calendar' | 'listings' | 'search' | 'users' | 'notFound' | 'default'
  /** Action button text */
  actionText?: string
  /** Action button route (for RouterLink) */
  actionTo?: string
  /** Whether to use a dashed border */
  dashed?: boolean
}

withDefaults(defineProps<Props>(), {
  subtitle: undefined,
  icon: 'default',
  actionText: undefined,
  actionTo: undefined,
  dashed: true,
})

defineEmits<{
  action: []
}>()
</script>

<template>
  <div
    class="flex flex-col items-center justify-center rounded-2xl bg-gray-50 py-20"
    :class="dashed ? 'border border-dashed border-gray-300' : 'border border-gray-200'"
  >
    <!-- Icon slot or default icons -->
    <div class="mb-6 flex h-20 w-20 items-center justify-center rounded-full bg-gray-100">
      <slot name="icon">
        <!-- Messages icon -->
        <svg
          v-if="icon === 'messages'"
          class="h-10 w-10 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"
          />
        </svg>

        <!-- Bookings/Calendar icon -->
        <svg
          v-else-if="icon === 'bookings' || icon === 'calendar'"
          class="h-10 w-10 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
          />
        </svg>

        <!-- Listings/Home icon -->
        <svg
          v-else-if="icon === 'listings'"
          class="h-10 w-10 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
          />
        </svg>

        <!-- Search icon -->
        <svg
          v-else-if="icon === 'search'"
          class="h-10 w-10 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>

        <!-- Users icon -->
        <svg
          v-else-if="icon === 'users'"
          class="h-10 w-10 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"
          />
        </svg>

        <!-- Default icon -->
        <svg
          v-else
          class="h-10 w-10 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"
          />
        </svg>
      </slot>
    </div>

    <!-- Title -->
    <h3 class="text-xl font-semibold text-[#222222]">{{ title }}</h3>

    <!-- Subtitle -->
    <p v-if="subtitle" class="mt-2 max-w-md text-center text-sm text-gray-600">
      {{ subtitle }}
    </p>

    <!-- Action button -->
    <RouterLink
      v-if="actionTo && actionText"
      :to="actionTo"
      class="mt-6 rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"
    >
      {{ actionText }}
    </RouterLink>

    <button
      v-else-if="actionText"
      type="button"
      class="mt-6 rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:shadow-md"
      @click="$emit('action')"
    >
      {{ actionText }}
    </button>

    <!-- Custom slot for additional content -->
    <slot />
  </div>
</template>
