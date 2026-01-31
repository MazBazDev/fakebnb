import { apiFetch } from '@/services/api'

export type Listing = {
  id: number
  host_user_id: number
  host?: {
    id: number
    name: string
    profile_photo_url?: string | null
  }
  title: string
  description: string
  city: string
  address: string
  full_address?: string | null
  latitude?: number | null
  longitude?: number | null
  guest_capacity: number
  price_per_night: number
  rules: string | null
  amenities?: string[]
  can_book?: boolean | null
  conversation_id?: number | null
  cohost_permissions?: {
    can_read_conversations: boolean
    can_reply_messages: boolean
    can_edit_listings: boolean
  } | null
  images?: ListingImage[]
  created_at?: string | null
}

export type ListingImage = {
  id: number
  url: string
  position: number
}

type ListingResponse = { data: Listing }
type ListingsResponse = {
  data: Listing[]
  meta?: {
    current_page: number
    last_page: number
    per_page: number
    total: number
  }
}

export async function fetchListings(filters?: {
  search?: string
  city?: string
  min_guests?: number
  bounds?: string
  padding_km?: number
  page?: number
  per_page?: number
}) {
  const params = new URLSearchParams()
  if (filters?.search) params.set('search', filters.search)
  if (filters?.city) params.set('city', filters.city)
  if (filters?.min_guests) params.set('min_guests', String(filters.min_guests))
  if (filters?.bounds) params.set('bounds', filters.bounds)
  if (filters?.padding_km) params.set('padding_km', String(filters.padding_km))
  if (filters?.page) params.set('page', String(filters.page))
  if (filters?.per_page) params.set('per_page', String(filters.per_page))

  const query = params.toString()
  const response = await apiFetch<ListingsResponse>(`/listings${query ? `?${query}` : ''}`)
  return response
}

export async function fetchListing(id: number) {
  const response = await apiFetch<ListingResponse>(`/listings/${id}`)
  return response.data
}

export async function fetchMyListings(filters?: {
  search?: string
  page?: number
  per_page?: number
}) {
  const params = new URLSearchParams()
  if (filters?.search) params.set('search', filters.search)
  if (filters?.page) params.set('page', String(filters.page))
  if (filters?.per_page) params.set('per_page', String(filters.per_page))

  const query = params.toString()
  const response = await apiFetch<ListingsResponse>(`/me/listings${query ? `?${query}` : ''}`)
  return response
}

export async function fetchCohostListings(filters?: {
  search?: string
  page?: number
  per_page?: number
}) {
  const params = new URLSearchParams()
  if (filters?.search) params.set('search', filters.search)
  if (filters?.page) params.set('page', String(filters.page))
  if (filters?.per_page) params.set('per_page', String(filters.per_page))

  const query = params.toString()
  const response = await apiFetch<ListingsResponse>(
    `/me/cohost-listings${query ? `?${query}` : ''}`
  )
  return response
}

export async function createListing(
  payload: Omit<Listing, 'id' | 'host_user_id' | 'created_at' | 'images'>
) {
  const response = await apiFetch<ListingResponse>('/listings', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
  return response.data
}

export async function uploadListingImages(listingId: number, files: File[]) {
  const formData = new FormData()
  files.forEach((file) => formData.append('images[]', file))

  const response = await fetch(
    `${import.meta.env.VITE_API_URL ?? '/api/v1'}/listings/${listingId}/images`,
    {
      method: 'POST',
      body: formData,
      headers: {
        Authorization: `Bearer ${localStorage.getItem('auth.token') ?? ''}`,
      },
    }
  )

  if (!response.ok) {
    const payload = await response.json().catch(() => null)
    throw new Error(payload?.message ?? 'Erreur upload images.')
  }

  return (await response.json()) as ListingResponse
}

export async function reorderListingImages(listingId: number, imageIds: number[]) {
  const response = await apiFetch<ListingResponse>(`/listings/${listingId}/images/reorder`, {
    method: 'PATCH',
    body: JSON.stringify({ image_ids: imageIds }),
  })
  return response.data
}

export async function updateListing(
  id: number,
  payload: Partial<Omit<Listing, 'id' | 'host_user_id' | 'created_at'>>
) {
  const response = await apiFetch<ListingResponse>(`/listings/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
  return response.data
}

export async function deleteListing(id: number) {
  return apiFetch<{ message: string }>(`/listings/${id}`, {
    method: 'DELETE',
  })
}
