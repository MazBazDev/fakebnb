<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, RouterLink, useRouter } from 'vue-router'
import { fetchListing, type Listing } from '@/services/listings'
import { createBooking, fetchActiveBookingCount, fetchListingBookings, type Booking } from '@/services/bookings'
import { createConversation } from '@/services/conversations'
import { fetchListingReviews, type Review } from '@/services/reviews'
import { useAuthStore } from '@/stores/auth'
import { getAmenityLabel } from '@/constants/amenities'
import { PageHeader, LoadingSpinner, AlertMessage } from '@/components/ui'

const route = useRoute()
const router = useRouter()
const auth = useAuthStore()
const listing = ref<Listing | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)
const bookingError = ref<string | null>(null)
const bookingSuccess = ref<string | null>(null)
const isSubmitting = ref(false)
const messageError = ref<string | null>(null)
const isMessaging = ref(false)
const bookingStatus = ref<'pending' | 'confirmed' | 'rejected' | null>(null)
const blockedDates = ref<Set<string>>(new Set())
const myActiveBookingsCount = ref(0)
const lightboxOpen = ref(false)
const lightboxImage = ref<string | null>(null)
const reviews = ref<Review[]>([])
const reviewsMeta = ref<{ total: number } | null>(null)
const bookingForm = ref({
  start_date: '',
  end_date: '',
})
const now = new Date()
const activeMonth = ref(new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), 1)))

function formatDate(date: Date) {
  return date.toISOString().slice(0, 10)
}

const monthLabel = computed(() =>
  activeMonth.value.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
)

const reviewsAverage = computed(() => {
  if (reviews.value.length === 0) return 0
  const total = reviews.value.reduce((sum, review) => sum + review.rating, 0)
  return Math.round((total / reviews.value.length) * 10) / 10
})

const reviewsCount = computed(() => reviewsMeta.value?.total ?? reviews.value.length)

const calendarDays = computed(() => {
  const year = activeMonth.value.getUTCFullYear()
  const month = activeMonth.value.getUTCMonth()
  const first = new Date(Date.UTC(year, month, 1))
  const startDay = (first.getUTCDay() + 6) % 7
  const daysInMonth = new Date(Date.UTC(year, month + 1, 0)).getUTCDate()
  const days: { date: Date; label: number; isOutside: boolean }[] = []

  for (let i = 0; i < startDay; i += 1) {
    const date = new Date(Date.UTC(year, month, -(startDay - 1 - i)))
    days.push({ date, label: date.getUTCDate(), isOutside: true })
  }

  for (let day = 1; day <= daysInMonth; day += 1) {
    const date = new Date(Date.UTC(year, month, day))
    days.push({ date, label: day, isOutside: false })
  }

  while (days.length % 7 !== 0) {
    const lastEntry = days[days.length - 1]
    if (!lastEntry) break
    const last = lastEntry.date
    const next = new Date(Date.UTC(last.getUTCFullYear(), last.getUTCMonth(), last.getUTCDate() + 1))
    days.push({ date: next, label: next.getUTCDate(), isOutside: true })
  }

  return days
})

function isBlocked(date: Date) {
  return blockedDates.value.has(formatDate(date))
}

function selectDate(date: Date) {
  if (isBlocked(date) || isPast(date)) return
  const value = formatDate(date)

  if (!bookingForm.value.start_date || bookingForm.value.end_date) {
    bookingForm.value.start_date = value
    bookingForm.value.end_date = ''
    bookingError.value = null
    return
  }

  if (value <= bookingForm.value.start_date) {
    bookingForm.value.start_date = value
    bookingError.value = null
    return
  }

  if (rangeHasBlocked(bookingForm.value.start_date, value)) {
    bookingError.value = 'La période sélectionnée chevauche des dates indisponibles.'
    return
  }

  bookingForm.value.end_date = value
  bookingError.value = null
}

function isSelected(date: Date) {
  const value = formatDate(date)
  return bookingForm.value.start_date === value || bookingForm.value.end_date === value
}

function isInRange(date: Date) {
  if (!bookingForm.value.start_date || !bookingForm.value.end_date) return false
  const value = formatDate(date)
  return value > bookingForm.value.start_date && value < bookingForm.value.end_date
}

function isPast(date: Date) {
  const today = new Date()
  const todayValue = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate()))
  return date < todayValue
}

function rangeHasBlocked(start: string, end: string) {
  const cursor = new Date(start)
  const endDate = new Date(end)
  while (cursor < endDate) {
    if (blockedDates.value.has(cursor.toISOString().slice(0, 10))) {
      return true
    }
    cursor.setDate(cursor.getDate() + 1)
  }
  return false
}

function previousMonth() {
  activeMonth.value = new Date(
    Date.UTC(activeMonth.value.getUTCFullYear(), activeMonth.value.getUTCMonth() - 1, 1)
  )
}

function nextMonth() {
  activeMonth.value = new Date(
    Date.UTC(activeMonth.value.getUTCFullYear(), activeMonth.value.getUTCMonth() + 1, 1)
  )
}

function formatStatus(status?: string | null) {
  if (status === 'confirmed') return 'Confirmée'
  if (status === 'rejected') return 'Refusée'
  return 'En attente'
}

function statusClass(status?: string | null) {
  if (status === 'confirmed') return 'bg-emerald-50 text-emerald-600 border-emerald-100'
  if (status === 'rejected') return 'bg-rose-50 text-rose-600 border-rose-100'
  return 'bg-amber-50 text-amber-600 border-amber-100'
}

async function loadActiveBookings() {
  if (!auth.isAuthenticated) {
    myActiveBookingsCount.value = 0
    return
  }

  try {
    myActiveBookingsCount.value = await fetchActiveBookingCount()
  } catch {
    myActiveBookingsCount.value = 0
  }
}

const nightsCount = computed(() => {
  if (!bookingForm.value.start_date || !bookingForm.value.end_date) return 0
  const start = new Date(bookingForm.value.start_date)
  const end = new Date(bookingForm.value.end_date)
  const diffMs = end.getTime() - start.getTime()
  return diffMs > 0 ? Math.ceil(diffMs / (1000 * 60 * 60 * 24)) : 0
})

const totalPrice = computed(() => {
  if (!listing.value) return 0
  return nightsCount.value * listing.value.price_per_night
})

function openLightbox(url?: string | null) {
  if (!url) return
  lightboxImage.value = url
  lightboxOpen.value = true
}

function closeLightbox() {
  lightboxOpen.value = false
  lightboxImage.value = null
}

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const id = Number(route.params.id)
    const [listingData, confirmed, reviewsResponse] = await Promise.all([
      fetchListing(id),
      fetchListingBookings(id),
      fetchListingReviews(id, 20),
    ])
    listing.value = listingData
    reviews.value = reviewsResponse.data
    reviewsMeta.value = reviewsResponse.meta ? { total: reviewsResponse.meta.total } : null
    await loadActiveBookings()
    blockedDates.value = new Set(
      confirmed.flatMap((booking) => {
        const start = new Date(booking.start_date)
        const end = new Date(booking.end_date)
        const dates: string[] = []
        const cursor = new Date(start)
        while (cursor < end) {
          dates.push(cursor.toISOString().slice(0, 10))
          cursor.setDate(cursor.getDate() + 1)
        }
        return dates
      })
    )
    bookingStatus.value = null
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger l’annonce.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)
watch(
  () => auth.isAuthenticated,
  (isAuthed) => {
    if (isAuthed) {
      loadActiveBookings()
    } else {
      myActiveBookingsCount.value = 0
    }
  }
)

async function submitBooking() {
  bookingError.value = null
  bookingSuccess.value = null
  isSubmitting.value = true

  try {
    if (!listing.value) {
      throw new Error('Annonce introuvable.')
    }

    if (!bookingForm.value.start_date || !bookingForm.value.end_date) {
      throw new Error('Veuillez sélectionner des dates.')
    }
    if (blockedDates.value.has(bookingForm.value.start_date)) {
      throw new Error('La date d’arrivée sélectionnée est indisponible.')
    }
    if (blockedDates.value.has(bookingForm.value.end_date)) {
      throw new Error('La date de départ sélectionnée est indisponible.')
    }
    if (rangeHasBlocked(bookingForm.value.start_date, bookingForm.value.end_date)) {
      throw new Error('La période sélectionnée chevauche des dates indisponibles.')
    }

    await createBooking({
      listing_id: listing.value.id,
      start_date: bookingForm.value.start_date,
      end_date: bookingForm.value.end_date,
    })
    bookingSuccess.value = 'Demande envoyée. En attente de validation de l’hôte.'
    bookingForm.value = { start_date: '', end_date: '' }
    bookingStatus.value = 'pending'
    await load()
  } catch (err) {
    bookingError.value = err instanceof Error ? err.message : 'Impossible de réserver.'
  } finally {
    isSubmitting.value = false
  }
}

async function contactHost() {
  messageError.value = null
  isMessaging.value = true

  try {
    if (!listing.value) {
      throw new Error('Annonce introuvable.')
    }

    const conversation = await createConversation(listing.value.id)
    await router.push(`/messages/${conversation.id}`)
  } catch (err) {
    messageError.value =
      err instanceof Error ? err.message : 'Impossible d’ouvrir la conversation.'
  } finally {
    isMessaging.value = false
  }
}
</script>

<template>
  <section class="space-y-8">
    <RouterLink
      to="/listings"
      class="inline-flex items-center gap-2 text-sm font-semibold text-gray-600 transition hover:text-[#222222]"
    >
      <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
      </svg>
      Retour aux annonces
    </RouterLink>

    <AlertMessage v-if="error" :message="error" type="error" />

    <LoadingSpinner v-if="isLoading" text="Chargement de l'annonce..." full-container />

    <div v-else-if="listing" class="space-y-10">
      <header class="space-y-4">
        <PageHeader
          :title="listing.title"
          :subtitle="listing.address"
          :breadcrumbs="[
            { label: 'Accueil', to: '/' },
            { label: 'Annonces', to: '/listings' },
            { label: listing.title },
          ]"
        />
        <div class="flex flex-wrap items-center gap-3 text-xs uppercase tracking-[0.2em] text-slate-400">
          <span>{{ listing.city }}</span>
          <span class="h-1 w-1 rounded-full bg-slate-300"></span>
          <span>Annonce #{{ listing.id }}</span>
          <RouterLink
            v-if="myActiveBookingsCount > 0"
            to="/bookings"
            class="ml-auto rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] font-semibold text-slate-600 hover:text-slate-900"
          >
            Voir mes réservations ({{ myActiveBookingsCount }})
          </RouterLink>
        </div>
        <div v-if="listing.host" class="flex items-center gap-3">
          <span
            class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-sm font-semibold text-slate-700"
          >
            <img
              v-if="listing.host.profile_photo_url"
              :src="listing.host.profile_photo_url"
              class="h-full w-full object-cover"
            />
            <span v-else>{{ listing.host.name?.[0] ?? '?' }}</span>
          </span>
          <div class="text-sm text-slate-500">
            <p class="font-semibold text-slate-700">Hôte</p>
            <p>{{ listing.host.name }}</p>
          </div>
        </div>
      </header>

      <div v-if="listing.images?.length" class="space-y-4">
        <div class="grid gap-4 lg:grid-cols-3">
          <div class="lg:col-span-2">
            <button
              type="button"
              class="group relative h-80 w-full overflow-hidden rounded-3xl border border-slate-200 md:h-[420px]"
              @click="openLightbox(listing.images?.[0]?.url)"
            >
              <img
                :src="listing.images?.[0]?.url"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                alt=""
              />
            </button>
          </div>
          <div class="grid gap-4">
            <button
              v-for="image in listing.images.slice(1, 3)"
              :key="image.id"
              type="button"
              class="group relative h-36 w-full overflow-hidden rounded-3xl border border-slate-200 md:h-[200px]"
              @click="openLightbox(image.url)"
            >
              <img
                :src="image.url"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                alt=""
              />
            </button>
          </div>
        </div>

        <div v-if="listing.images.length > 3" class="overflow-x-auto">
          <div class="flex gap-3">
            <button
              v-for="image in listing.images.slice(3)"
              :key="image.id"
              type="button"
              class="group relative h-24 w-32 flex-shrink-0 overflow-hidden rounded-2xl border border-slate-200"
              @click="openLightbox(image.url)"
            >
              <img
                :src="image.url"
                class="h-full w-full object-cover transition duration-300 group-hover:scale-[1.02]"
                alt=""
              />
            </button>
          </div>
        </div>
      </div>

      <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
        <div class="space-y-8">
          <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-4">
              <p class="text-xl font-semibold text-slate-900">
                {{ listing.price_per_night }} €/nuit
              </p>
              <span class="text-xs font-semibold text-slate-500">
                Capacité {{ listing.guest_capacity }} personne{{ listing.guest_capacity > 1 ? 's' : '' }}
              </span>
            </div>

            <div class="mt-4 flex flex-wrap items-center gap-3 text-sm text-slate-600">
              <div class="flex items-center gap-1 text-rose-600">
                <span v-for="star in 5" :key="star" class="text-base">
                  {{ star <= Math.round(reviewsAverage) ? '★' : '☆' }}
                </span>
              </div>
              <span>{{ reviewsAverage }} / 5</span>
              <span class="text-slate-400">•</span>
              <span>{{ reviewsCount }} avis</span>
            </div>

            <div
              v-if="bookingStatus"
              class="mt-4 inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold"
              :class="statusClass(bookingStatus)"
            >
              Statut réservation: {{ formatStatus(bookingStatus) }}
            </div>

            <p class="mt-4 text-sm text-slate-600">{{ listing.description }}</p>

            <div
              v-if="listing.amenities?.length"
              class="mt-6 flex flex-wrap items-center gap-2"
            >
              <span
                v-for="amenity in listing.amenities"
                :key="amenity"
                class="rounded-full border border-slate-200 px-3 py-1 text-xs text-slate-600"
              >
                {{ getAmenityLabel(amenity) }}
              </span>
            </div>

            <div
              v-if="listing.rules"
              class="mt-6 rounded-2xl bg-slate-50 p-4 text-sm text-slate-600"
            >
              <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Règles</p>
              <p class="mt-2">{{ listing.rules }}</p>
            </div>

            <div class="mt-6 flex flex-wrap items-center gap-3">
              <button
                v-if="listing.conversation_id"
                class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
                type="button"
                @click="router.push(`/messages/${listing.conversation_id}`)"
              >
                Reprendre la conversation
              </button>
              <button
                v-else-if="listing.can_book !== false"
                class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
                type="button"
                :disabled="isMessaging"
                @click="contactHost"
              >
                {{ isMessaging ? 'Ouverture...' : 'Contacter l’hôte' }}
              </button>
              <p v-else class="text-xs text-slate-500">Vous gérez cette annonce.</p>
              <p v-if="messageError" class="text-xs text-rose-600">{{ messageError }}</p>
            </div>
          </div>

          <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-4 flex items-center justify-between">
              <h2 class="text-lg font-semibold text-slate-900">Avis</h2>
              <span class="text-xs font-semibold text-slate-500">{{ reviewsCount }} avis</span>
            </div>

            <div v-if="reviews.length === 0" class="text-sm text-slate-500">
              Aucun avis pour le moment.
            </div>

            <div v-else class="space-y-4">
              <article
                v-for="review in reviews"
                :key="review.id"
                class="rounded-2xl border border-slate-100 bg-slate-50 p-4"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-slate-700">
                      {{ review.guest?.name ?? `Voyageur #${review.guest_user_id}` }}
                    </span>
                    <span class="text-xs text-slate-400">•</span>
                    <span class="text-xs text-slate-400">{{ review.created_at?.slice(0, 10) }}</span>
                  </div>
                  <div class="flex items-center gap-1 text-rose-600">
                    <span v-for="star in 5" :key="star" class="text-sm">
                      {{ star <= review.rating ? '★' : '☆' }}
                    </span>
                  </div>
                </div>
                <p class="mt-3 text-sm text-slate-600">{{ review.comment }}</p>
                <div
                  v-if="review.reply_body"
                  class="mt-4 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600"
                >
                  <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Réponse de l'hôte</p>
                  <p class="mt-2">{{ review.reply_body }}</p>
                </div>
              </article>
            </div>
          </div>
        </div>

        <aside class="space-y-4 lg:sticky lg:top-8">
          <div
            v-if="listing.can_book === false"
            class="rounded-3xl border border-slate-200 bg-white p-6 text-sm text-slate-600 shadow-sm"
          >
            Vous ne pouvez pas réserver votre propre annonce.
          </div>

          <form
            v-else
            class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
            @submit.prevent="submitBooking"
          >
            <div class="flex items-center justify-between">
              <h2 class="text-sm font-semibold text-slate-700">Réserver ce logement</h2>
              <span v-if="bookingStatus" class="text-xs text-slate-400">
                Demande en attente
              </span>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
              <div class="flex items-center justify-between">
                <button
                  type="button"
                  class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600"
                  @click="previousMonth"
                >
                  ←
                </button>
                <span class="text-sm font-semibold text-slate-700">{{ monthLabel }}</span>
                <button
                  type="button"
                  class="rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600"
                  @click="nextMonth"
                >
                  →
                </button>
              </div>

              <div class="mt-4 grid grid-cols-7 gap-2 text-xs text-slate-400">
                <span class="text-center">Lu</span>
                <span class="text-center">Ma</span>
                <span class="text-center">Me</span>
                <span class="text-center">Je</span>
                <span class="text-center">Ve</span>
                <span class="text-center">Sa</span>
                <span class="text-center">Di</span>
              </div>

              <div class="mt-2 grid grid-cols-7 gap-2">
                <button
                  v-for="day in calendarDays"
                  :key="day.date.toISOString()"
                  type="button"
                  class="flex h-10 items-center justify-center rounded-xl text-sm transition"
                  :class="[
                    day.isOutside ? 'text-slate-300' : 'text-slate-700',
                    isBlocked(day.date) || isPast(day.date)
                      ? 'cursor-not-allowed bg-rose-50 text-rose-300'
                      : 'hover:bg-slate-900 hover:text-white',
                    isInRange(day.date) ? 'bg-slate-200 text-slate-900' : '',
                    isSelected(day.date) ? 'bg-slate-900 text-white' : '',
                  ]"
                  :disabled="day.isOutside || isBlocked(day.date) || isPast(day.date)"
                  @click="selectDate(day.date)"
                >
                  {{ day.label }}
                </button>
              </div>

              <div class="mt-4 flex flex-wrap items-center gap-2 text-xs text-slate-500">
                <span class="rounded-full border border-slate-200 bg-white px-2 py-1">
                  Disponible
                </span>
                <span
                  class="rounded-full border border-rose-100 bg-rose-50 px-2 py-1 text-rose-600"
                >
                  Indisponible
                </span>
                <span class="rounded-full border border-slate-200 bg-slate-200 px-2 py-1">
                  Sélection
                </span>
              </div>
            </div>

            <div class="space-y-2 text-[11px] text-slate-500">
              <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-2">
                <span class="font-semibold uppercase tracking-[0.2em] text-slate-400">Arrivée</span>
                <span class="text-xs font-semibold text-slate-900">
                  {{ bookingForm.start_date || 'Sélectionner une date' }}
                </span>
              </div>
              <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-white px-3 py-2">
                <span class="font-semibold uppercase tracking-[0.2em] text-slate-400">Départ</span>
                <span class="text-xs font-semibold text-slate-900">
                  {{ bookingForm.end_date || 'Sélectionner une date' }}
                </span>
              </div>
              <div class="flex items-center justify-between rounded-2xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                <span class="font-semibold uppercase tracking-[0.2em] text-slate-400">Total</span>
                <span class="text-sm font-semibold text-slate-900">
                  {{ nightsCount > 0 ? `${totalPrice} € (${nightsCount} nuit${nightsCount > 1 ? 's' : ''})` : '—' }}
                </span>
              </div>
            </div>

            <AlertMessage v-if="bookingError" :message="bookingError" type="error" />
            <AlertMessage v-if="bookingSuccess" :message="bookingSuccess" type="success" />

            <button
              class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="isSubmitting"
              type="submit"
            >
              {{ isSubmitting ? 'Réservation...' : 'Réserver' }}
            </button>
          </form>
        </aside>
      </div>
    </div>
  </section>

  <div
    v-if="lightboxOpen && lightboxImage"
    class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/70 px-6 py-10"
    role="dialog"
    aria-modal="true"
    @click.self="closeLightbox"
  >
    <div class="relative max-h-full w-full max-w-5xl">
      <button
        type="button"
        class="absolute right-4 top-4 rounded-full border border-slate-200 bg-white/90 px-3 py-1 text-xs font-semibold text-slate-700"
        @click="closeLightbox"
      >
        Fermer
      </button>
      <div class="rounded-3xl border border-white/20 bg-white/90">
        <img
          :src="lightboxImage"
          class="max-h-[80vh] w-full rounded-2xl bg-white object-contain"
          alt=""
        />
      </div>
    </div>
  </div>
</template>
