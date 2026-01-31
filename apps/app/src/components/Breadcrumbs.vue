<script setup lang="ts">
import { RouterLink } from 'vue-router'

type BreadcrumbItem = {
  label: string
  to?: string
}

defineProps<{ items: BreadcrumbItem[] }>()
</script>

<template>
  <nav aria-label="Breadcrumb" class="text-xs font-semibold uppercase tracking-[0.2em]">
    <ol class="flex flex-wrap items-center gap-2 text-slate-400">
      <li v-for="(item, index) in items" :key="`${item.label}-${index}`" class="flex items-center gap-2">
        <RouterLink
          v-if="item.to && index < items.length - 1"
          :to="item.to"
          class="hover:text-slate-600"
        >
          {{ item.label }}
        </RouterLink>
        <span v-else class="text-slate-500">{{ item.label }}</span>
        <span v-if="index < items.length - 1" class="text-slate-300">/</span>
      </li>
    </ol>
  </nav>
</template>
