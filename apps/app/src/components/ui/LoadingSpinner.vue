<script setup lang="ts">
interface Props {
  /** Text to display below the spinner */
  text?: string
  /** Size of the spinner: sm, md, lg */
  size?: 'sm' | 'md' | 'lg'
  /** Whether to show the full container with background */
  fullContainer?: boolean
}

withDefaults(defineProps<Props>(), {
  text: 'Chargement...',
  size: 'md',
  fullContainer: true,
})

const sizeClasses = {
  sm: 'h-6 w-6 border-2',
  md: 'h-10 w-10 border-4',
  lg: 'h-14 w-14 border-4',
}
</script>

<template>
  <div
    v-if="fullContainer"
    class="flex items-center justify-center rounded-2xl border border-gray-100 bg-gray-50 py-20"
  >
    <div class="text-center">
      <div
        :class="[
          'mx-auto animate-spin rounded-full border-gray-200 border-t-[#FF385C]',
          sizeClasses[size],
        ]"
      />
      <p v-if="text" class="mt-4 text-sm text-gray-600">{{ text }}</p>
    </div>
  </div>

  <div v-else class="flex items-center justify-center">
    <div
      :class="[
        'animate-spin rounded-full border-gray-200 border-t-[#FF385C]',
        sizeClasses[size],
      ]"
    />
    <span v-if="text" class="ml-3 text-sm text-gray-600">{{ text }}</span>
  </div>
</template>
