import { apiFetch } from '@/services/api'

export type CohostPermissions = {
  can_read_conversations: boolean
  can_reply_messages: boolean
  can_edit_listings: boolean
}

export type CohostUser = {
  id: number
  name: string
  email: string
}

export type Cohost = {
  id: number
  host_user_id: number
  cohost_user_id: number
  can_read_conversations: boolean
  can_reply_messages: boolean
  can_edit_listings: boolean
  cohost?: CohostUser
}

export function fetchCohosts() {
  return apiFetch<Cohost[]>('/cohosts')
}

export function createCohost(payload: {
  cohost_user_id: number
  can_read_conversations?: boolean
  can_reply_messages?: boolean
  can_edit_listings?: boolean
}) {
  return apiFetch<Cohost>('/cohosts', {
    method: 'POST',
    body: JSON.stringify(payload),
  })
}

export function updateCohost(id: number, payload: Partial<CohostPermissions>) {
  return apiFetch<Cohost>(`/cohosts/${id}`, {
    method: 'PATCH',
    body: JSON.stringify(payload),
  })
}

export function deleteCohost(id: number) {
  return apiFetch<{ message: string }>(`/cohosts/${id}`, {
    method: 'DELETE',
  })
}
