/**
 * Composable for date formatting utilities.
 *
 * @example
 * ```ts
 * const { formatRelativeDate, formatDate, formatDateRange } = useDateFormat()
 *
 * // In template:
 * // {{ formatRelativeDate(message.created_at) }}
 * // {{ formatDate(booking.check_in) }}
 * ```
 */
export function useDateFormat() {
  /**
   * Format a date as a relative string (Aujourd'hui, Hier, Il y a X jours, etc.)
   */
  function formatRelativeDate(dateString: string | null | undefined): string {
    if (!dateString) return ''

    const date = new Date(dateString)
    const now = new Date()
    const diff = now.getTime() - date.getTime()
    const days = Math.floor(diff / (1000 * 60 * 60 * 24))

    if (days === 0) return "Aujourd'hui"
    if (days === 1) return 'Hier'
    if (days < 7) return `Il y a ${days} jours`

    return date.toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
    })
  }

  /**
   * Format a date in French locale (e.g., "12 janv. 2024")
   */
  function formatDate(
    dateString: string | Date | null | undefined,
    options: Intl.DateTimeFormatOptions = { day: 'numeric', month: 'short', year: 'numeric' }
  ): string {
    if (!dateString) return ''

    const date = typeof dateString === 'string' ? new Date(dateString) : dateString
    return date.toLocaleDateString('fr-FR', options)
  }

  /**
   * Format a date in short format (e.g., "12 janv.")
   */
  function formatDateShort(dateString: string | Date | null | undefined): string {
    return formatDate(dateString, { day: 'numeric', month: 'short' })
  }

  /**
   * Format a date in long format (e.g., "Lundi 12 janvier 2024")
   */
  function formatDateLong(dateString: string | Date | null | undefined): string {
    return formatDate(dateString, {
      weekday: 'long',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    })
  }

  /**
   * Format a date range (e.g., "12 - 15 janv. 2024" or "12 janv. - 3 févr. 2024")
   */
  function formatDateRange(
    startDate: string | Date | null | undefined,
    endDate: string | Date | null | undefined
  ): string {
    if (!startDate || !endDate) return ''

    const start = typeof startDate === 'string' ? new Date(startDate) : startDate
    const end = typeof endDate === 'string' ? new Date(endDate) : endDate

    const sameMonth = start.getMonth() === end.getMonth()
    const sameYear = start.getFullYear() === end.getFullYear()

    if (sameMonth && sameYear) {
      // "12 - 15 janv. 2024"
      return `${start.getDate()} - ${end.toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
      })}`
    }

    if (sameYear) {
      // "12 janv. - 3 févr. 2024"
      return `${start.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' })} - ${end.toLocaleDateString('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' })}`
    }

    // "12 janv. 2024 - 3 févr. 2025"
    return `${formatDate(start)} - ${formatDate(end)}`
  }

  /**
   * Format a date to ISO format for forms (YYYY-MM-DD)
   */
  function toISODate(date: Date): string {
    return date.toISOString().slice(0, 10)
  }

  /**
   * Calculate the number of nights between two dates
   */
  function calculateNights(
    startDate: string | Date | null | undefined,
    endDate: string | Date | null | undefined
  ): number {
    if (!startDate || !endDate) return 0

    const start = typeof startDate === 'string' ? new Date(startDate) : startDate
    const end = typeof endDate === 'string' ? new Date(endDate) : endDate
    const diff = end.getTime() - start.getTime()

    return Math.max(0, Math.ceil(diff / (1000 * 60 * 60 * 24)))
  }

  return {
    formatRelativeDate,
    formatDate,
    formatDateShort,
    formatDateLong,
    formatDateRange,
    toISODate,
    calculateNights,
  }
}
