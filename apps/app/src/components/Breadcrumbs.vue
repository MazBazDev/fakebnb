<script setup lang="ts">
import { RouterLink } from 'vue-router'

type BreadcrumbItem = {
  label: string
  to?: string
}

defineProps<{ items: BreadcrumbItem[] }>()
</script>

<template>
  <nav aria-label="Breadcrumb" class="mb-4">
    <ol class="flex flex-wrap items-center gap-2 text-sm text-gray-600">
      <li v-for="(item, index) in items" :key="`${item.label}-${index}`" class="flex items-center gap-2">
        <RouterLink
          v-if="item.to && index < items.length - 1"
          :to="item.to"
          class="transition hover:text-[#222222] hover:underline"
        >
          {{ item.label }}
        </RouterLink>
        <span v-else :class="index === items.length - 1 ? 'font-semibold text-[#222222]' : ''">
          {{ item.label }}
        </span>
        <svg
          v-if="index < items.length - 1"
          class="h-3 w-3 text-gray-400"
          fill="none"
          viewBox="0 0 24 24"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M9 5l7 7-7 7"
          />
        </svg>
      </li>
    </ol>
  </nav>
</template>
