<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue'

interface BreadcrumbItem {
  label: string
  to?: string
}

interface Props {
  /** Page title */
  title: string
  /** Optional subtitle */
  subtitle?: string
  /** Breadcrumb items */
  breadcrumbs?: BreadcrumbItem[]
}

defineProps<Props>()
</script>

<template>
  <header class="space-y-4">
    <!-- Breadcrumbs -->
    <Breadcrumbs v-if="breadcrumbs?.length" :items="breadcrumbs" />

    <!-- Title row with optional action slot -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
      <div>
        <h1 class="text-4xl font-semibold tracking-tight text-[#222222] sm:text-5xl">
          {{ title }}
        </h1>
        <p v-if="subtitle" class="mt-2 text-lg text-gray-600">
          {{ subtitle }}
        </p>
      </div>

      <!-- Action slot (for buttons, etc.) -->
      <div v-if="$slots.actions" class="flex shrink-0 items-center gap-3">
        <slot name="actions" />
      </div>
    </div>

    <!-- Extra content slot -->
    <slot />
  </header>
</template>
