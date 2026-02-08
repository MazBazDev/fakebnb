import { apiFetch } from '@/services/api'

export type Review = {
  id: number
  booking_id: number
  listing_id: number
  guest_user_id: number
  guest?: {
    id: number
    name: string
    profile_photo_url?: string | null
  }
  listing?: {
    id: number
    title: string
    city: string
  }
  rating: number
  comment: string
  reply_body?: string | null
  replied_at?: string | null
  replied_by?: {
    id: number
    name: string
    profile_photo_url?: string | null
  } | null
  can_reply?: boolean
  created_at?: string | null
}

type ReviewResponse = { data: Review }

type ReviewsResponse = {
  data: Review[]
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

type CreateReviewPayload = {
  rating: number
  comment: string
}

export async function fetchListingReviews(listingId: number, perPage = 10) {
  const response = await apiFetch<ReviewsResponse>(`/listings/${listingId}/reviews?per_page=${perPage}`)
  return response
}

export async function createReview(bookingId: number, payload: CreateReviewPayload) {
  const response = await apiFetch<ReviewResponse>(`/bookings/${bookingId}/reviews`, {
    method: 'POST',
    body: JSON.stringify(payload),
  })
  return response.data
}

export async function fetchHostReviews(
  listingId?: number,
  perPage = 20,
  scope?: 'host' | 'cohost'
) {
  const params = new URLSearchParams()
  params.set('per_page', String(perPage))
  if (listingId) params.set('listing_id', String(listingId))
  if (scope) params.set('scope', scope)

  const response = await apiFetch<ReviewsResponse>(`/host/reviews?${params.toString()}`)
  return response
}

export async function replyToReview(reviewId: number, replyBody: string) {
  const response = await apiFetch<ReviewResponse>(`/reviews/${reviewId}/reply`, {
    method: 'POST',
    body: JSON.stringify({ reply_body: replyBody }),
  })
  return response.data
}
