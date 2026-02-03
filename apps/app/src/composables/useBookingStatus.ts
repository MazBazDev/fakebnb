export type BookingStatus =
  | 'pending'
  | 'confirmed'
  | 'awaiting_payment'
  | 'completed'
  | 'cancelled'
  | 'rejected'

export interface StatusConfig {
  label: string
  class: string
}

const STATUS_CONFIG: Record<BookingStatus, StatusConfig> = {
  pending: {
    label: 'En attente',
    class: 'bg-blue-50 text-blue-700 border-blue-200',
  },
  confirmed: {
    label: 'Confirmée',
    class: 'bg-green-50 text-green-700 border-green-200',
  },
  awaiting_payment: {
    label: 'Paiement requis',
    class: 'bg-amber-50 text-amber-700 border-amber-200',
  },
  completed: {
    label: 'Terminée',
    class: 'bg-gray-100 text-gray-700 border-gray-200',
  },
  cancelled: {
    label: 'Annulée',
    class: 'bg-gray-100 text-gray-500 border-gray-200',
  },
  rejected: {
    label: 'Refusée',
    class: 'bg-red-50 text-red-700 border-red-200',
  },
}

const DEFAULT_CONFIG: StatusConfig = {
  label: 'Inconnu',
  class: 'bg-gray-100 text-gray-700 border-gray-200',
}

/**
 * Composable for booking status labels and CSS classes.
 *
 * @example
 * ```ts
 * const { statusLabel, statusClass, getStatusConfig } = useBookingStatus()
 *
 * // In template:
 * // <span :class="statusClass(booking.status)">{{ statusLabel(booking.status) }}</span>
 * ```
 */
export function useBookingStatus() {
  /**
   * Get the localized label for a booking status.
   */
  function statusLabel(status: string): string {
    const config = STATUS_CONFIG[status as BookingStatus]
    return config?.label ?? DEFAULT_CONFIG.label
  }

  /**
   * Get the CSS classes for a booking status badge.
   */
  function statusClass(status: string): string {
    const config = STATUS_CONFIG[status as BookingStatus]
    return config?.class ?? DEFAULT_CONFIG.class
  }

  /**
   * Get both label and class for a booking status.
   */
  function getStatusConfig(status: string): StatusConfig {
    return STATUS_CONFIG[status as BookingStatus] ?? DEFAULT_CONFIG
  }

  /**
   * Check if a booking can be cancelled.
   */
  function canCancel(status: string): boolean {
    return ['pending', 'confirmed', 'awaiting_payment'].includes(status)
  }

  /**
   * Check if a booking is in a final state.
   */
  function isFinalState(status: string): boolean {
    return ['completed', 'cancelled', 'rejected'].includes(status)
  }

  return {
    statusLabel,
    statusClass,
    getStatusConfig,
    canCancel,
    isFinalState,
    STATUS_CONFIG,
  }
}
