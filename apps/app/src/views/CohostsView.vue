<script setup lang="ts">
import { computed, ref } from 'vue'
import {
  createCohost,
  deleteCohost,
  fetchCohosts,
  updateCohost,
  type Cohost,
} from '@/services/cohosts'
import { fetchMyListings, type Listing } from '@/services/listings'
import { useAsyncData, useFormSubmit } from '@/composables'
import { PageHeader, LoadingSpinner, EmptyState, AlertMessage } from '@/components/ui'

// Data
const cohosts = ref<Cohost[]>([])
const listings = ref<Listing[]>([])
const actionError = ref<string | null>(null)

// Form state
const form = ref({
  listing_id: '',
  cohost_email: '',
  can_read_conversations: false,
  can_reply_messages: false,
  can_edit_listings: false,
})

// Data fetching
const { isLoading, error: loadError } = useAsyncData(
  async () => {
    const listingsResponse = await fetchMyListings({ per_page: 100 })
    listings.value = listingsResponse.data ?? []
    const totalListings = listingsResponse.meta?.total ?? listingsResponse.data.length

    if (totalListings === 0) {
      throw new Error('Accès réservé aux hôtes ayant des annonces.')
    }

    const cohostsData = await fetchCohosts()
    cohosts.value = cohostsData

    return { listings: listings.value, cohosts: cohostsData }
  },
  { errorMessage: 'Impossible de charger les co-hôtes.' }
)

// Form submission for creating cohost
const { isSubmitting: creating, error: createError, submit: submitCreate } = useFormSubmit(
  async () => {
    const created = await createCohost({
      listing_id: Number(form.value.listing_id),
      cohost_email: form.value.cohost_email,
      can_read_conversations: form.value.can_read_conversations,
      can_reply_messages: form.value.can_reply_messages,
      can_edit_listings: form.value.can_edit_listings,
    })

    cohosts.value = [created, ...cohosts.value]
    resetForm()
    return created
  },
  { errorMessage: 'Impossible de créer le co-hôte.' }
)

// Computed
const breadcrumbs = [
  { label: 'Hôte', to: '/host' },
  { label: 'Co-hôtes' },
]

const displayError = computed(() => loadError.value || createError.value || actionError.value)

// Methods
function resetForm() {
  form.value = {
    listing_id: '',
    cohost_email: '',
    can_read_conversations: false,
    can_reply_messages: false,
    can_edit_listings: false,
  }
}

type PermissionKey = 'can_read_conversations' | 'can_reply_messages' | 'can_edit_listings'

async function togglePermission(cohost: Cohost, key: PermissionKey) {
  actionError.value = null
  const nextValue = !cohost[key]

  try {
    const updated = await updateCohost(cohost.id, { [key]: nextValue })
    cohosts.value = cohosts.value.map((item) => (item.id === updated.id ? updated : item))
  } catch (err) {
    actionError.value = err instanceof Error ? err.message : 'Impossible de mettre à jour.'
  }
}

async function removeCohost(cohost: Cohost) {
  actionError.value = null

  if (!confirm(`Êtes-vous sûr de vouloir supprimer ce co-hôte ?`)) return

  try {
    await deleteCohost(cohost.id)
    cohosts.value = cohosts.value.filter((item) => item.id !== cohost.id)
  } catch (err) {
    actionError.value = err instanceof Error ? err.message : 'Impossible de supprimer.'
  }
}

function getCohostName(cohost: Cohost): string {
  return cohost.cohost?.name ?? `Utilisateur #${cohost.cohost_user_id}`
}

function getListingName(cohost: Cohost): string {
  return cohost.listing?.title ?? `Annonce #${cohost.listing_id}`
}

</script>

<template>
  <section class="space-y-8">
    <PageHeader
      title="Gérer les délégations"
      subtitle="Ajoutez un co-hôte par email et configurez ses permissions par annonce"
      :breadcrumbs="breadcrumbs"
    />

    <!-- Error Messages -->
    <AlertMessage v-if="displayError" :message="displayError" type="error" />

    <div class="grid gap-6 lg:grid-cols-5">
      <!-- Form Card -->
      <div class="lg:col-span-2">
        <form
          class="space-y-5 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
          @submit.prevent="submitCreate"
        >
          <h2 class="text-lg font-semibold text-[#222222]">Ajouter un co-hôte</h2>

          <!-- Listing Select -->
          <div class="space-y-2">
            <label for="listing" class="block text-sm font-semibold text-[#222222]">
              Annonce
            </label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                  />
                </svg>
              </div>
              <select
                id="listing"
                v-model="form.listing_id"
                class="w-full appearance-none rounded-lg border border-gray-300 bg-white py-3 pl-10 pr-10 text-base text-[#222222] transition focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
                required
              >
                <option value="" disabled>Choisir une annonce</option>
                <option v-for="listing in listings" :key="listing.id" :value="listing.id">
                  {{ listing.title }} — {{ listing.city }}
                </option>
              </select>
              <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </div>
            </div>
          </div>

          <!-- Email Input -->
          <div class="space-y-2">
            <label for="cohost_email" class="block text-sm font-semibold text-[#222222]">
              Email du co-hôte
            </label>
            <div class="relative">
              <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                  />
                </svg>
              </div>
              <input
                id="cohost_email"
                v-model="form.cohost_email"
                type="email"
                class="w-full rounded-lg border border-gray-300 py-3 pl-10 pr-4 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
                placeholder="exemple@email.com"
                required
              />
            </div>
          </div>

          <!-- Permissions -->
          <div class="space-y-3">
            <label class="block text-sm font-semibold text-[#222222]">Permissions</label>
            <div class="space-y-2">
              <label
                class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 transition hover:border-gray-300 has-[:checked]:border-black has-[:checked]:bg-gray-50"
              >
                <input
                  v-model="form.can_read_conversations"
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-[#222222] focus:ring-black"
                />
                <span class="text-sm text-[#222222]">Lire les conversations</span>
              </label>
              <label
                class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 transition hover:border-gray-300 has-[:checked]:border-black has-[:checked]:bg-gray-50"
              >
                <input
                  v-model="form.can_reply_messages"
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-[#222222] focus:ring-black"
                />
                <span class="text-sm text-[#222222]">Répondre aux messages</span>
              </label>
              <label
                class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 px-4 py-3 transition hover:border-gray-300 has-[:checked]:border-black has-[:checked]:bg-gray-50"
              >
                <input
                  v-model="form.can_edit_listings"
                  type="checkbox"
                  class="h-4 w-4 rounded border-gray-300 text-[#222222] focus:ring-black"
                />
                <span class="text-sm text-[#222222]">Modifier les annonces</span>
              </label>
            </div>
          </div>

          <!-- Submit Button -->
          <button
            class="w-full rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
            :disabled="creating"
            type="submit"
          >
            {{ creating ? 'Création en cours...' : 'Ajouter un co-hôte' }}
          </button>
        </form>
      </div>

      <!-- Cohosts List -->
      <div class="lg:col-span-3">
        <LoadingSpinner v-if="isLoading" text="Chargement des co-hôtes..." full-container />

        <EmptyState
          v-else-if="cohosts.length === 0"
          title="Aucun co-hôte"
          subtitle="Ajoutez votre premier co-hôte pour déléguer la gestion de vos annonces"
          icon="users"
          dashed
        />

        <div v-else class="space-y-4">
          <h2 class="text-lg font-semibold text-[#222222]">
            {{ cohosts.length }} co-hôte{{ cohosts.length > 1 ? 's' : '' }}
          </h2>

          <div class="space-y-3">
            <article
              v-for="cohost in cohosts"
              :key="cohost.id"
              class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm transition hover:shadow-md"
            >
              <!-- Header -->
              <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                  <!-- Avatar -->
                  <div
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-orange-500 text-lg font-semibold text-white"
                  >
                    {{ getCohostName(cohost).charAt(0).toUpperCase() }}
                  </div>
                  <div>
                    <p class="font-semibold text-[#222222]">{{ getCohostName(cohost) }}</p>
                    <p class="text-sm text-gray-500">{{ cohost.cohost?.email }}</p>
                    <p class="mt-1 text-xs text-gray-400">{{ getListingName(cohost) }}</p>
                  </div>
                </div>

                <button
                  class="inline-flex items-center gap-1.5 text-sm font-medium text-red-600 transition hover:text-red-700"
                  type="button"
                  @click="removeCohost(cohost)"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                  Supprimer
                </button>
              </div>

              <!-- Permissions -->
              <div class="mt-4 grid gap-3 border-t border-gray-100 pt-4 sm:grid-cols-3">
                <label
                  class="flex cursor-pointer items-center gap-2 text-sm"
                  @click.prevent="togglePermission(cohost, 'can_read_conversations')"
                >
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-[#222222] focus:ring-black"
                    :checked="cohost.can_read_conversations"
                    @click.prevent
                  />
                  <span :class="cohost.can_read_conversations ? 'text-[#222222]' : 'text-gray-500'">
                    Lire les conversations
                  </span>
                </label>

                <label
                  class="flex cursor-pointer items-center gap-2 text-sm"
                  @click.prevent="togglePermission(cohost, 'can_reply_messages')"
                >
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-[#222222] focus:ring-black"
                    :checked="cohost.can_reply_messages"
                    @click.prevent
                  />
                  <span :class="cohost.can_reply_messages ? 'text-[#222222]' : 'text-gray-500'">
                    Répondre
                  </span>
                </label>

                <label
                  class="flex cursor-pointer items-center gap-2 text-sm"
                  @click.prevent="togglePermission(cohost, 'can_edit_listings')"
                >
                  <input
                    type="checkbox"
                    class="h-4 w-4 rounded border-gray-300 text-[#222222] focus:ring-black"
                    :checked="cohost.can_edit_listings"
                    @click.prevent
                  />
                  <span :class="cohost.can_edit_listings ? 'text-[#222222]' : 'text-gray-500'">
                    Modifier annonces
                  </span>
                </label>
              </div>
            </article>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
