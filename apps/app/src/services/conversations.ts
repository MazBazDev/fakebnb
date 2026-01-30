import { apiFetch } from '@/services/api'

export type Conversation = {
  id: number
  listing_id: number
  host_user_id: number
  guest_user_id: number
  listing?: { id: number; title: string; city: string }
  last_message?: {
    id: number
    body: string
    sender_user_id: number
    created_at?: string | null
  } | null
}

type ConversationsResponse = { data: Conversation[] }
type ConversationResponse = { data: Conversation }

export async function fetchConversations() {
  const response = await apiFetch<ConversationsResponse>('/conversations')
  return response.data
}

export async function createConversation(listingId: number) {
  const response = await apiFetch<ConversationResponse>('/conversations', {
    method: 'POST',
    body: JSON.stringify({ listing_id: listingId }),
  })
  return response.data
}
