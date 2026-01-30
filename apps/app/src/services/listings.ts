import { apiFetch } from '@/services/api'

export type Listing = {
  id: number
  host_user_id: number
  title: string
  description: string
  city: string
  address: string
  price_per_night: number
  rules: string | null
  images?: ListingImage[]
  created_at?: string | null
}

export type ListingImage = {
  id: number
  url: string
  position: number
}

type ListingResponse = { data: Listing }
type ListingsResponse = { data: Listing[] }

export async function fetchListings() {
  const response = await apiFetch<ListingsResponse>('/listings')
  return response.data
}

export async function fetchListing(id: number) {
  const response = await apiFetch<ListingResponse>(`/listings/${id}`)
  return response.data
}

export async function fetchMyListings() {
  const response = await apiFetch<ListingsResponse>('/me/listings')
  return response.data
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
