<script setup lang="ts">
import { useBookingStatus, type BookingStatus } from '@/composables/useBookingStatus'

interface Props {
  /** The booking status */
  status: BookingStatus | string
  /** Size variant */
  size?: 'sm' | 'md'
}

withDefaults(defineProps<Props>(), {
  size: 'md',
})

const { statusLabel, statusClass } = useBookingStatus()

const sizeClasses = {
  sm: 'px-2 py-1 text-xs',
  md: 'px-3 py-1.5 text-xs',
}
</script>

<template>
  <span
    :class="[
      'inline-flex items-center rounded-full border font-semibold',
      sizeClasses[size],
      statusClass(status),
    ]"
  >
    {{ statusLabel(status) }}
  </span>
</template>
