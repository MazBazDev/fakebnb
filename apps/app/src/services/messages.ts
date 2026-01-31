import { apiFetch } from '@/services/api'

export type Message = {
  id: number
  conversation_id: number
  sender_user_id: number
  sender?: {
    id: number
    name: string
    profile_photo_url?: string | null
  }
  body: string
  created_at?: string | null
}

type MessagesResponse = { data: Message[]; meta?: { can_reply?: boolean } }
type MessageResponse = { data: Message }

export async function fetchMessages(conversationId: number) {
  const response = await apiFetch<MessagesResponse>(`/conversations/${conversationId}/messages`)
  return response
}

export async function sendMessage(conversationId: number, body: string) {
  const response = await apiFetch<MessageResponse>(`/conversations/${conversationId}/messages`, {
    method: 'POST',
    body: JSON.stringify({ body }),
  })
  return response.data
}
