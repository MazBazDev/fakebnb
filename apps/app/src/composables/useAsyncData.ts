import { ref, onMounted, type Ref } from 'vue'

export interface UseAsyncDataOptions<T> {
  /** Execute the fetcher immediately on mount (default: true) */
  immediate?: boolean
  /** Default error message if the error is not an Error instance */
  errorMessage?: string
  /** Default value for data before fetch completes */
  defaultValue?: T
}

export interface UseAsyncDataReturn<T, D = T | null> {
  /** The fetched data */
  data: Ref<D>
  /** Loading state */
  isLoading: Ref<boolean>
  /** Error message if the fetch failed */
  error: Ref<string | null>
  /** Execute the fetch function */
  execute: () => Promise<void>
  /** Alias for execute */
  refresh: () => Promise<void>
}

/**
 * Composable for handling async data fetching with loading and error states.
 *
 * @example
 * ```ts
 * // Without default value - data can be null
 * const { data: booking, isLoading, error } = useAsyncData(
 *   () => fetchBooking(id),
 *   { errorMessage: 'Impossible de charger la réservation.' }
 * )
 *
 * // With default value - data is never null
 * const { data: bookings, isLoading, error } = useAsyncData(
 *   () => fetchBookings(),
 *   { defaultValue: [], errorMessage: 'Impossible de charger les réservations.' }
 * )
 * ```
 */
export function useAsyncData<T>(
  fetcher: () => Promise<T>,
  options?: UseAsyncDataOptions<T | null>
): UseAsyncDataReturn<T, T | null>
export function useAsyncData<T, D extends T | null>(
  fetcher: () => Promise<T>,
  options: UseAsyncDataOptions<D>
): UseAsyncDataReturn<T, D>
export function useAsyncData<T, D extends T | null = T | null>(
  fetcher: () => Promise<T>,
  options: UseAsyncDataOptions<D> = {}
): UseAsyncDataReturn<T, D> {
  const { immediate = true, errorMessage = 'Une erreur est survenue.', defaultValue } = options

  const data = ref<D>((defaultValue ?? null) as D)
  const isLoading = ref(false)
  const error = ref<string | null>(null)

  async function execute(): Promise<void> {
    isLoading.value = true
    error.value = null

    try {
      data.value = await fetcher()
    } catch (err) {
      error.value = err instanceof Error ? err.message : errorMessage
    } finally {
      isLoading.value = false
    }
  }

  if (immediate) {
    onMounted(execute)
  }

  return {
    data,
    isLoading,
    error,
    execute,
    refresh: execute,
  } as UseAsyncDataReturn<T, D>
}
