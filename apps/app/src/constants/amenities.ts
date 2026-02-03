export interface AmenityOption {
  id: string
  label: string
  icon?: string
}

/**
 * List of available amenities for listings.
 * Used in ListingCreateView, ListingEditView, and ListingDetailView.
 */
export const AMENITY_OPTIONS: readonly AmenityOption[] = [
  { id: 'wifi', label: 'Wi-Fi' },
  { id: 'kitchen', label: 'Cuisine' },
  { id: 'parking', label: 'Parking gratuit' },
  { id: 'washer', label: 'Lave-linge' },
  { id: 'dryer', label: 'Sèche-linge' },
  { id: 'tv', label: 'TV' },
  { id: 'air_conditioning', label: 'Climatisation' },
  { id: 'heating', label: 'Chauffage' },
  { id: 'workspace', label: 'Espace de travail' },
  { id: 'pool', label: 'Piscine' },
  { id: 'hot_tub', label: 'Jacuzzi' },
] as const

/**
 * Get the label for an amenity by its ID.
 */
export function getAmenityLabel(amenityId: string): string {
  const option = AMENITY_OPTIONS.find((a) => a.id === amenityId)
  return option?.label ?? amenityId.replace(/_/g, ' ')
}

/**
 * Get amenity options as a map for quick lookup.
 */
export const AMENITY_MAP = new Map(AMENITY_OPTIONS.map((a) => [a.id, a]))
