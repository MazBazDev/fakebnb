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
  created_at?: string | null
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

export async function createListing(payload: Omit<Listing, 'id' | 'host_user_id' | 'created_at'>) {
  const response = await apiFetch<ListingResponse>('/listings', {
    method: 'POST',
    body: JSON.stringify(payload),
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
