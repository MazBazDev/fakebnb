import { ref, type Ref } from 'vue'

export interface UseFormSubmitOptions {
  /** Success message to display after successful submission */
  successMessage?: string
  /** Default error message if the error is not an Error instance */
  errorMessage?: string
  /** Callback to execute on success */
  onSuccess?: () => void | Promise<void>
  /** Callback to execute on error */
  onError?: (error: Error) => void
}

export interface UseFormSubmitReturn<T> {
  /** Submitting state */
  isSubmitting: Ref<boolean>
  /** Error message if the submission failed */
  error: Ref<string | null>
  /** Success message if the submission succeeded */
  success: Ref<string | null>
  /** Execute the submit function */
  submit: () => Promise<T | undefined>
  /** Reset error and success states */
  reset: () => void
}

/**
 * Composable for handling form submissions with loading, error, and success states.
 *
 * @example
 * ```ts
 * const { isSubmitting, error, success, submit } = useFormSubmit(
 *   () => updateProfile(form.value),
 *   {
 *     successMessage: 'Profil mis à jour avec succès.',
 *     errorMessage: 'Impossible de mettre à jour le profil.',
 *     onSuccess: () => router.push('/profile')
 *   }
 * )
 * ```
 */
export function useFormSubmit<T>(
  submitter: () => Promise<T>,
  options: UseFormSubmitOptions = {}
): UseFormSubmitReturn<T> {
  const {
    successMessage,
    errorMessage = 'Une erreur est survenue.',
    onSuccess,
    onError,
  } = options

  const isSubmitting = ref(false)
  const error = ref<string | null>(null)
  const success = ref<string | null>(null)

  function reset(): void {
    error.value = null
    success.value = null
  }

  async function submit(): Promise<T | undefined> {
    reset()
    isSubmitting.value = true

    try {
      const result = await submitter()
      success.value = successMessage ?? null

      if (onSuccess) {
        await onSuccess()
      }

      return result
    } catch (err) {
      const errorInstance = err instanceof Error ? err : new Error(errorMessage)
      error.value = errorInstance.message

      if (onError) {
        onError(errorInstance)
      }

      return undefined
    } finally {
      isSubmitting.value = false
    }
  }

  return {
    isSubmitting,
    error,
    success,
    submit,
    reset,
  }
}
