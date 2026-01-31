import { apiFetch } from '@/services/api'
import type { Booking } from '@/services/bookings'

export type HostStats = {
  listings_count: number
  pending_count: number
  awaiting_payment_count: number
  confirmed_count: number
  total_payout: number
  upcoming_arrivals: Booking[]
  recent_requests: Booking[]
  series: {
    months: string[]
    booking_counts: number[]
    payout_totals: number[]
  }
}

export async function fetchHostStats() {
  return apiFetch<HostStats>('/me/host-stats')
}
