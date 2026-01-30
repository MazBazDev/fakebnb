import { apiFetch } from '@/services/api'

export type Booking = {
  id: number
  listing_id: number
  guest_user_id: number
  guest?: {
    id: number
    name: string
    profile_photo_url?: string | null
  }
  start_date: string
  end_date: string
  status: 'pending' | 'confirmed' | 'rejected'
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

export async function fetchListingBookings(listingId: number) {
  const response = await apiFetch<BookingsResponse>(`/listings/${listingId}/bookings`)
  return response.data
}

export async function confirmBooking(id: number) {
  const response = await apiFetch<BookingResponse>(`/bookings/${id}/confirm`, {
    method: 'PATCH',
  })
  return response.data
}

export async function rejectBooking(id: number) {
  const response = await apiFetch<BookingResponse>(`/bookings/${id}/reject`, {
    method: 'PATCH',
  })
  return response.data
}
