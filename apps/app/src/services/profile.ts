export type ProfilePayload = {
  name: string
  address?: string | null
  photo?: File | null
}

export async function updateProfile(payload: ProfilePayload) {
  const formData = new FormData()
  formData.append('name', payload.name)
  if (payload.address) {
    formData.append('address', payload.address)
  }
  if (payload.photo) {
    formData.append('photo', payload.photo)
  }

  const response = await fetch(
    `${import.meta.env.VITE_API_URL ?? '/api/v1'}/me/profile`,
    {
      method: 'PATCH',
      body: formData,
      headers: {
        Accept: 'application/json',
        Authorization: `Bearer ${localStorage.getItem('auth.access_token') ?? ''}`,
      },
    }
  )

  if (!response.ok) {
    const payload = await response.json().catch(() => null)
    throw new Error(payload?.message ?? 'Erreur mise à jour profil.')
  }

  return response.json()
}
