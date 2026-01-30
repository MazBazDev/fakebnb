import { apiFetch } from '@/services/api'

export type Message = {
  id: number
  conversation_id: number
  sender_user_id: number
  body: string
  created_at?: string | null
}

type MessagesResponse = { data: Message[] }
type MessageResponse = { data: Message }

export async function fetchMessages(conversationId: number) {
  const response = await apiFetch<MessagesResponse>(`/conversations/${conversationId}/messages`)
  return response.data
}

export async function sendMessage(conversationId: number, body: string) {
  const response = await apiFetch<MessageResponse>(`/conversations/${conversationId}/messages`, {
    method: 'POST',
    body: JSON.stringify({ body }),
  })
  return response.data
}
