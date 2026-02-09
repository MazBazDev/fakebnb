import { apiFetch } from '@/services/api'

export type Payment = {
  id: number
  booking_id: number
  guest_user_id: number
  host_user_id: number
  amount_total: number
  amount_base: number
  amount_vat: number
  amount_service: number
  commission_amount: number
  payout_amount: number
  cashoula_direct_applied?: boolean
  cashoula_direct_won?: boolean
  status: 'requires_authorization' | 'authorized' | 'captured' | 'failed' | 'refunded'
  created_at?: string | null
}

type PaymentResponse = { data: Payment }

export async function createPaymentIntent(bookingId: number) {
  return apiFetch<PaymentResponse>('/payments/intent', {
    method: 'POST',
    body: JSON.stringify({ booking_id: bookingId }),
  })
}

export async function authorizePayment(paymentId: number) {
  return apiFetch<PaymentResponse>(`/payments/${paymentId}/authorize`, {
    method: 'POST',
  })
}
