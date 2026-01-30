import { apiFetch } from '@/services/api'

export function becomeHost() {
  return apiFetch<{ message: string }>('/me/host', {
    method: 'POST',
  })
}
