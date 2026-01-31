<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import {
  createCohost,
  deleteCohost,
  fetchCohosts,
  updateCohost,
  type Cohost,
} from '@/services/cohosts'
import { fetchMyListings, type Listing } from '@/services/listings'

const cohosts = ref<Cohost[]>([])
const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const creating = ref(false)
const form = ref({
  listing_id: '',
  cohost_email: '',
  can_read_conversations: false,
  can_reply_messages: false,
  can_edit_listings: false,
})

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const listingsResponse = await fetchMyListings({ per_page: 1 })
    listings.value = listingsResponse.data ?? []
    const totalListings = listingsResponse.meta?.total ?? listingsResponse.data.length

    if (totalListings === 0) {
      error.value = 'Accès réservé aux hôtes ayant des annonces.'
      return
    }

    const cohostsData = await fetchCohosts()
    cohosts.value = cohostsData
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les co-hôtes.'
  } finally {
    isLoading.value = false
  }
}

async function submit() {
  error.value = null
  creating.value = true

  try {
    const created = await createCohost({
      listing_id: Number(form.value.listing_id),
      cohost_email: form.value.cohost_email,
      can_read_conversations: form.value.can_read_conversations,
      can_reply_messages: form.value.can_reply_messages,
      can_edit_listings: form.value.can_edit_listings,
    })
    cohosts.value = [created, ...cohosts.value]
    form.value = {
      listing_id: '',
      cohost_email: '',
      can_read_conversations: false,
      can_reply_messages: false,
      can_edit_listings: false,
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de créer le co-hôte.'
  } finally {
    creating.value = false
  }
}

type PermissionKey = 'can_read_conversations' | 'can_reply_messages' | 'can_edit_listings'

async function togglePermission(cohost: Cohost, key: PermissionKey) {
  error.value = null
  const nextValue = !cohost[key]

  try {
    const updated = await updateCohost(cohost.id, { [key]: nextValue })
    cohosts.value = cohosts.value.map((item) => (item.id === updated.id ? updated : item))
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de mettre à jour.'
  }
}

async function removeCohost(cohost: Cohost) {
  error.value = null

  try {
    await deleteCohost(cohost.id)
    cohosts.value = cohosts.value.filter((item) => item.id !== cohost.id)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de supprimer.'
  }
}

onMounted(load)
</script>

<template>
  <section class="space-y-8">
    <header class="space-y-2">
      <Breadcrumbs :items="[{ label: 'Hôte', to: '/host' }, { label: 'Co-hôtes' }]" />
      <h1 class="text-3xl font-semibold text-slate-900">Gérer les délégations</h1>
      <p class="text-sm text-slate-500">
        Ajoute un co-hôte par email et configure ses permissions par annonce.
      </p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[1fr_1.4fr]">
      <form
        class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
        @submit.prevent="submit"
      >
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Annonce</label>
          <select
            v-model="form.listing_id"
            class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
            required
          >
            <option value="" disabled>Choisir une annonce</option>
            <option v-for="listing in listings" :key="listing.id" :value="listing.id">
              {{ listing.title }} — {{ listing.city }}
            </option>
          </select>
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Email du co-hôte</label>
          <input
            v-model="form.cohost_email"
            type="email"
            class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
            required
          />
        </div>

        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Permissions</label>
          <label class="flex items-center gap-2 text-sm text-slate-600">
            <input v-model="form.can_read_conversations" type="checkbox" />
            Lire les conversations
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-600">
            <input v-model="form.can_reply_messages" type="checkbox" />
            Répondre aux messages
          </label>
          <label class="flex items-center gap-2 text-sm text-slate-600">
            <input v-model="form.can_edit_listings" type="checkbox" />
            Modifier les annonces
          </label>
        </div>

        <button
          class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="creating"
          type="submit"
        >
          {{ creating ? 'Création...' : 'Ajouter un co-hôte' }}
        </button>
      </form>

      <div class="space-y-4">
        <div v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
          {{ error }}
        </div>

        <div
          v-if="isLoading"
          class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
        >
          Chargement des co-hôtes...
        </div>

        <div
          v-else-if="cohosts.length === 0"
          class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-sm text-slate-500"
        >
          Aucun co-hôte pour le moment.
        </div>

        <div v-else class="space-y-3">
          <article
            v-for="cohost in cohosts"
            :key="cohost.id"
            class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
          >
            <div class="flex items-center justify-between">
              <div>
                <p class="text-sm font-semibold text-slate-800">
                  {{ cohost.cohost?.name ?? 'Utilisateur #' + cohost.cohost_user_id }}
                </p>
                <p class="text-xs text-slate-500">{{ cohost.cohost?.email }}</p>
                <p class="text-xs text-slate-400">
                  {{ cohost.listing?.title ?? 'Annonce #' + cohost.listing_id }}
                </p>
              </div>
              <button
                class="text-xs font-semibold text-rose-600 hover:text-rose-700"
                type="button"
                @click="removeCohost(cohost)"
              >
                Supprimer
              </button>
            </div>

            <div class="mt-4 grid gap-3 text-sm text-slate-600 md:grid-cols-3">
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :checked="cohost.can_read_conversations"
                  @change="togglePermission(cohost, 'can_read_conversations')"
                />
                Lire les conversations
              </label>
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :checked="cohost.can_reply_messages"
                  @change="togglePermission(cohost, 'can_reply_messages')"
                />
                Répondre
              </label>
              <label class="flex items-center gap-2">
                <input
                  type="checkbox"
                  :checked="cohost.can_edit_listings"
                  @change="togglePermission(cohost, 'can_edit_listings')"
                />
                Modifier annonces
              </label>
            </div>
          </article>
        </div>
      </div>
    </div>
  </section>
</template>
