import { apiFetch } from '@/services/api'

export type Booking = {
  id: number
  listing_id: number
  guest_user_id: number
  start_date: string
  end_date: string
  created_at?: string | null
}

type BookingResponse = { data: Booking }
type BookingsResponse = { data: Booking[] }

export async function fetchBookings() {
  const response = await apiFetch<BookingsResponse>('/bookings')
  return response.data
}

export async function createBooking(payload: {
  listing_id: number
  start_date: string
  end_date: string
}) {
  const response = await apiFetch<BookingResponse>('/bookings', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
  return response.data
}
