<script setup lang="ts">
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { fetchHostReviews, replyToReview, type Review } from '@/services/reviews'
import { fetchCohostListings, fetchMyListings, type Listing } from '@/services/listings'
import { useAsyncData, useDateFormat } from '@/composables'
import { AlertMessage, EmptyState, LoadingSpinner, PageHeader } from '@/components/ui'

const { formatRelativeDate, formatDate } = useDateFormat()

const listings = ref<Listing[]>([])
const selectedListingId = ref<number | ''>('')
const selectedScope = ref<'all' | 'host' | 'cohost'>('all')

const hostListings = ref<Listing[]>([])
const cohostListings = ref<Listing[]>([])

const replyDrafts = reactive<Record<number, string>>({})
const replyErrors = reactive<Record<number, string>>({})
const replySubmitting = reactive<Record<number, boolean>>({})

const {
  data: reviews,
  isLoading,
  error,
  execute: reload,
} = useAsyncData<Review[]>(
  async () => {
    const listingId = selectedListingId.value ? Number(selectedListingId.value) : undefined
    const scope = selectedScope.value === 'all' ? undefined : selectedScope.value
    const response = await fetchHostReviews(listingId, 50, scope)
    return response.data ?? []
  },
  { errorMessage: 'Impossible de charger les avis.' }
)

const listingOptions = computed(() => {
  const source =
    selectedScope.value === 'host'
      ? hostListings.value
      : selectedScope.value === 'cohost'
        ? cohostListings.value
        : listings.value

  const unique = new Map<number, Listing>()
  source.forEach((listing) => unique.set(listing.id, listing))
  return Array.from(unique.values())
})

async function loadListings() {
  const [hostResponse, cohostResponse] = await Promise.all([
    fetchMyListings({ per_page: 100 }),
    fetchCohostListings({ per_page: 100 }),
  ])
  hostListings.value = hostResponse.data ?? []
  cohostListings.value = cohostResponse.data ?? []
  listings.value = [...hostListings.value, ...cohostListings.value]
}

function getListingLabel(review: Review) {
  if (review.listing) {
    return `${review.listing.title} — ${review.listing.city}`
  }

  return `Annonce #${review.listing_id}`
}

async function submitReply(review: Review) {
  const draft = replyDrafts[review.id]?.trim() ?? ''
  replyErrors[review.id] = ''

  if (!draft) {
    replyErrors[review.id] = 'Ajoutez une réponse avant de publier.'
    return
  }

  if (replySubmitting[review.id]) return

  replySubmitting[review.id] = true

  try {
    const updated = await replyToReview(review.id, draft)
    replyDrafts[review.id] = ''

    if (reviews.value) {
      reviews.value = reviews.value.map((item) => (item.id === review.id ? updated : item))
    }
  } catch (err) {
    replyErrors[review.id] = 'Impossible de publier la réponse.'
    console.error(err)
  } finally {
    replySubmitting[review.id] = false
  }
}

watch([selectedListingId, selectedScope], () => {
  const listingIds = new Set(listingOptions.value.map((listing) => listing.id))
  if (selectedListingId.value && !listingIds.has(Number(selectedListingId.value))) {
    selectedListingId.value = ''
  }
  reload()
})

onMounted(async () => {
  await loadListings()
  await reload()
})
</script>

<template>
  <section class="mx-auto max-w-4xl space-y-8">
    <PageHeader
      title="Avis"
      subtitle="Retours des voyageurs sur vos annonces"
      :breadcrumbs="[{ label: 'Hôte', to: '/host' }, { label: 'Avis' }]"
    >
      <template #actions>
        <div class="flex flex-wrap items-center gap-3">
          <div class="flex items-center gap-2 rounded-full border border-slate-200 bg-white p-1 text-sm font-semibold text-slate-600">
            <button
              type="button"
              class="rounded-full px-3 py-1.5 transition"
              :class="selectedScope === 'all' ? 'bg-[#FF385C] text-white' : 'hover:text-[#FF385C]'"
              @click="selectedScope = 'all'"
            >
              Toutes
            </button>
            <button
              type="button"
              class="rounded-full px-3 py-1.5 transition"
              :class="selectedScope === 'host' ? 'bg-[#FF385C] text-white' : 'hover:text-[#FF385C]'"
              @click="selectedScope = 'host'"
            >
              Mes annonces
            </button>
            <button
              type="button"
              class="rounded-full px-3 py-1.5 transition"
              :class="selectedScope === 'cohost' ? 'bg-[#FF385C] text-white' : 'hover:text-[#FF385C]'"
              @click="selectedScope = 'cohost'"
            >
              Co-hôte
            </button>
          </div>
          <label class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Annonce</label>
          <select
            v-model="selectedListingId"
            class="rounded-full border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700"
          >
            <option value="">Toutes</option>
            <option v-for="listing in listingOptions" :key="listing.id" :value="listing.id">
              {{ listing.title }} — {{ listing.city }}
            </option>
          </select>
        </div>
      </template>
    </PageHeader>

    <AlertMessage v-if="error" :message="error" type="error" />

    <LoadingSpinner v-if="isLoading" text="Chargement des avis..." full-container />

    <EmptyState
      v-else-if="!reviews || reviews.length === 0"
      title="Aucun avis pour le moment"
      subtitle="Les avis des voyageurs apparaîtront ici"
      icon="messages"
      dashed
    />

    <div v-else class="space-y-4">
      <article
        v-for="review in reviews"
        :key="review.id"
        class="overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
      >
        <div class="flex flex-wrap items-start justify-between gap-4">
          <div class="space-y-1">
            <RouterLink
              v-if="review.listing_id"
              :to="`/host/listings/${review.listing_id}/edit`"
              class="text-lg font-semibold text-[#222222] hover:text-[#FF385C]"
            >
              {{ getListingLabel(review) }}
            </RouterLink>
            <p v-else class="text-lg font-semibold text-[#222222]">
              {{ getListingLabel(review) }}
            </p>
            <p class="text-sm text-gray-500">
              {{ review.guest?.name ?? 'Voyageur' }} · {{ formatDate(review.created_at) }}
            </p>
          </div>
          <span class="text-sm text-gray-400">{{ formatRelativeDate(review.created_at) }}</span>
        </div>

        <div class="mt-4 flex items-center gap-1 text-lg text-amber-400">
          <span v-for="star in 5" :key="star">
            {{ star <= review.rating ? '★' : '☆' }}
          </span>
        </div>

        <p class="mt-3 text-sm text-gray-700">{{ review.comment }}</p>

        <div v-if="review.reply_body" class="mt-5 rounded-xl border border-rose-100 bg-rose-50 p-4 text-sm text-gray-700">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-400">Réponse de l'hôte</p>
          <p class="mt-2">{{ review.reply_body }}</p>
        </div>

        <form
          v-else-if="review.can_reply"
          class="mt-5 space-y-3"
          @submit.prevent="submitReply(review)"
        >
          <label class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">
            Répondre à l'avis
          </label>
          <textarea
            v-model="replyDrafts[review.id]"
            rows="3"
            class="w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-700 focus:border-[#FF385C] focus:outline-none focus:ring-2 focus:ring-[#FF385C]/20"
            placeholder="Remerciez le voyageur et partagez votre réponse..."
          ></textarea>
          <p v-if="replyErrors[review.id]" class="text-xs text-rose-500">
            {{ replyErrors[review.id] }}
          </p>
          <div class="flex justify-end">
            <button
              type="submit"
              class="rounded-full bg-[#FF385C] px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-[#E61E4D] disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="replySubmitting[review.id]"
            >
              {{ replySubmitting[review.id] ? 'Publication...' : 'Publier la réponse' }}
            </button>
          </div>
        </form>
        <div v-else class="mt-5 rounded-xl border border-gray-100 bg-gray-50 p-4 text-sm text-gray-600">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-gray-400">Réponse</p>
          <p class="mt-2">Vous pouvez consulter cet avis mais vous n'avez pas les droits pour répondre.</p>
        </div>
      </article>
    </div>
  </section>
</template>
