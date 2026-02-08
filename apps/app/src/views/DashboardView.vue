<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { PageHeader, LoadingSpinner, EmptyState, AlertMessage } from '@/components/ui'
import { useAuthStore } from '@/stores/auth'
import { fetchHostStats, type HostStats } from '@/services/hostStats'
import { fetchBookings, type Booking } from '@/services/bookings'

const auth = useAuthStore()
const router = useRouter()
const displayName = computed(() => auth.user?.name ?? auth.user?.email ?? 'Utilisateur')
const stats = ref<HostStats | null>(null)
const bookings = ref<Booking[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const calendarMonth = ref(new Date())

const upcomingArrivals = computed(() => stats.value?.upcoming_arrivals ?? [])
const recentBookings = computed(() => stats.value?.recent_requests ?? [])
const bookingSeries = computed(() => stats.value?.series.booking_counts ?? [])
const payoutSeries = computed(() => stats.value?.series.payout_totals ?? [])

const calendarLabel = computed(() =>
  calendarMonth.value.toLocaleDateString('fr-FR', { month: 'long', year: 'numeric' })
)

const calendarDays = computed(() => {
  const monthStart = new Date(Date.UTC(calendarMonth.value.getUTCFullYear(), calendarMonth.value.getUTCMonth(), 1))
  const monthEnd = new Date(Date.UTC(calendarMonth.value.getUTCFullYear(), calendarMonth.value.getUTCMonth() + 1, 0))
  const firstWeekday = (monthStart.getUTCDay() + 6) % 7
  const totalDays = monthEnd.getUTCDate()

  const days: Array<{ date: Date | null; iso: string | null }> = []
  for (let i = 0; i < firstWeekday; i += 1) {
    days.push({ date: null, iso: null })
  }
  for (let day = 1; day <= totalDays; day += 1) {
    const date = new Date(Date.UTC(calendarMonth.value.getUTCFullYear(), calendarMonth.value.getUTCMonth(), day))
    days.push({ date, iso: date.toISOString().slice(0, 10) })
  }
  while (days.length % 7 !== 0) {
    days.push({ date: null, iso: null })
  }
  return days
})

const bookingDays = computed(() => {
  const set = new Set<string>()
  const monthStart = new Date(Date.UTC(calendarMonth.value.getUTCFullYear(), calendarMonth.value.getUTCMonth(), 1))
  const monthEnd = new Date(Date.UTC(calendarMonth.value.getUTCFullYear(), calendarMonth.value.getUTCMonth() + 1, 0))

  bookings.value
    .filter((booking) => ['confirmed', 'awaiting_payment'].includes(booking.status))
    .forEach((booking) => {
      const start = new Date(booking.start_date)
      const end = new Date(booking.end_date)
      const cursor = new Date(Date.UTC(start.getUTCFullYear(), start.getUTCMonth(), start.getUTCDate()))
      const endDate = new Date(Date.UTC(end.getUTCFullYear(), end.getUTCMonth(), end.getUTCDate()))

      while (cursor < endDate) {
        if (cursor >= monthStart && cursor <= monthEnd) {
          set.add(cursor.toISOString().slice(0, 10))
        }
        cursor.setUTCDate(cursor.getUTCDate() + 1)
      }
    })

  return set
})

const bookingsByDay = computed(() => {
  const map = new Map<string, Booking[]>()
  const monthStart = new Date(Date.UTC(calendarMonth.value.getUTCFullYear(), calendarMonth.value.getUTCMonth(), 1))
  const monthEnd = new Date(Date.UTC(calendarMonth.value.getUTCFullYear(), calendarMonth.value.getUTCMonth() + 1, 0))

  bookings.value
    .filter((booking) => ['confirmed', 'awaiting_payment'].includes(booking.status))
    .forEach((booking) => {
      const start = new Date(booking.start_date)
      const end = new Date(booking.end_date)
      const cursor = new Date(Date.UTC(start.getUTCFullYear(), start.getUTCMonth(), start.getUTCDate()))
      const endDate = new Date(Date.UTC(end.getUTCFullYear(), end.getUTCMonth(), end.getUTCDate()))

      while (cursor < endDate) {
        if (cursor >= monthStart && cursor <= monthEnd) {
          const iso = cursor.toISOString().slice(0, 10)
          const existing = map.get(iso) ?? []
          map.set(iso, [...existing, booking])
        }
        cursor.setUTCDate(cursor.getUTCDate() + 1)
      }
    })

  return map
})

// Stats cards configuration
const colorClasses = {
  blue: { bg: 'bg-blue-50', text: 'text-blue-600' },
  amber: { bg: 'bg-amber-50', text: 'text-amber-600' },
  purple: { bg: 'bg-purple-50', text: 'text-purple-600' },
  green: { bg: 'bg-green-50', text: 'text-green-600' },
} as const

type ColorKey = keyof typeof colorClasses
type IconKey = 'home' | 'clock' | 'card' | 'currency'

type StatCard = {
  label: string
  value: number | string
  icon: IconKey
  color: ColorKey
  link?: string
  linkText?: string
  subtitle?: string
  isFormatted?: boolean
}

const statCards = computed<StatCard[]>(() => [
  {
    label: 'Annonces actives',
    value: stats.value?.listings_count ?? 0,
    icon: 'home',
    color: 'blue',
    link: '/host/listings',
    linkText: 'Voir tout',
  },
  {
    label: 'En attente',
    value: stats.value?.pending_count ?? 0,
    icon: 'clock',
    color: 'amber',
    subtitle: 'Demandes à traiter',
  },
  {
    label: 'À payer',
    value: stats.value?.awaiting_payment_count ?? 0,
    icon: 'card',
    color: 'purple',
    subtitle: 'En attente de paiement',
  },
  {
    label: 'Revenus totaux',
    value: formatAmount(stats.value?.total_payout ?? 0),
    icon: 'currency',
    color: 'green',
    subtitle: 'Paiements confirmés',
    isFormatted: true,
  },
])

function listingLabel(listingId: number) {
  const listing = stats.value?.recent_requests.find((item) => item.listing_id === listingId)?.listing
  return listing ? `${listing.title} — ${listing.city}` : `Annonce #${listingId}`
}

function formatAmount(value: number) {
  return value.toLocaleString('fr-FR', { style: 'currency', currency: 'EUR' })
}

function sparklinePoints(values: number[], width = 400, height = 128, padding = 8) {
  if (values.length === 0) return ''
  const max = Math.max(...values, 1)
  const min = Math.min(...values, 0)
  const range = max - min || 1
  const step = values.length > 1 ? (width - padding * 2) / (values.length - 1) : 0
  return values
    .map((value, index) => {
      const x = padding + index * step
      const y = height - padding - ((value - min) / range) * (height - padding * 2)
      return `${x},${y}`
    })
    .join(' ')
}

function latestSeriesValue(values: number[]) {
  return values.length ? values[values.length - 1] ?? 0 : 0
}

async function loadDashboard() {
  isLoading.value = true
  error.value = null

  try {
    const [statsData, bookingsData] = await Promise.all([
      fetchHostStats(),
      fetchBookings(),
    ])
    stats.value = statsData
    bookings.value = bookingsData
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les statistiques.'
  } finally {
    isLoading.value = false
  }
}

function prevMonth() {
  const next = new Date(calendarMonth.value)
  next.setUTCMonth(next.getUTCMonth() - 1)
  calendarMonth.value = next
}

function nextMonth() {
  const next = new Date(calendarMonth.value)
  next.setUTCMonth(next.getUTCMonth() + 1)
  calendarMonth.value = next
}

function openBooking(bookingId: number) {
  router.push(`/host/bookings/${bookingId}`)
}

onMounted(loadDashboard)
</script>

<template>
  <section class="space-y-12">
    <PageHeader
      :title="`Bienvenue, ${displayName}`"
      subtitle="Gérez vos annonces et réservations"
      :breadcrumbs="[{ label: 'Hôte', to: '/host' }, { label: 'Tableau de bord' }]"
    >
      <template #actions>
        <RouterLink
          to="/host/listings/new"
          class="rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md"
        >
          Créer une annonce
        </RouterLink>
      </template>
    </PageHeader>

    <LoadingSpinner v-if="isLoading" text="Chargement des statistiques..." full-container />

    <AlertMessage v-else-if="error" :message="error" type="error" />

    <!-- Dashboard Content -->
    <div v-else class="space-y-8">
      <!-- Stats Cards Grid -->
      <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
        <div
          v-for="(card, index) in statCards"
          :key="index"
          class="group overflow-hidden rounded-2xl border border-gray-200 bg-white p-6 shadow-sm transition hover:shadow-md"
        >
          <div class="flex items-start justify-between">
            <div class="space-y-1">
              <p class="text-sm font-medium text-gray-500">{{ card.label }}</p>
              <p class="text-4xl font-semibold tracking-tight text-[#222222]">
                {{ card.isFormatted ? card.value : card.value }}
              </p>
            </div>
            <div :class="['rounded-lg p-3', colorClasses[card.color].bg]">
              <!-- Home Icon -->
              <svg v-if="card.icon === 'home'" :class="['h-6 w-6', colorClasses[card.color].text]" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
              </svg>
              <!-- Clock Icon -->
              <svg v-else-if="card.icon === 'clock'" :class="['h-6 w-6', colorClasses[card.color].text]" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <!-- Card Icon -->
              <svg v-else-if="card.icon === 'card'" :class="['h-6 w-6', colorClasses[card.color].text]" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
              </svg>
              <!-- Currency Icon -->
              <svg v-else-if="card.icon === 'currency'" :class="['h-6 w-6', colorClasses[card.color].text]" fill="none" viewBox="0 0 24 24">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <RouterLink
            v-if="card.link"
            :to="card.link"
            class="mt-4 inline-flex items-center text-sm font-medium text-gray-600 transition hover:text-[#222222]"
          >
            {{ card.linkText }}
            <svg class="ml-1 h-4 w-4" fill="none" viewBox="0 0 24 24">
              <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </RouterLink>
          <p v-else-if="card.subtitle" class="mt-4 text-sm text-gray-600">{{ card.subtitle }}</p>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid gap-6 lg:grid-cols-2">
        <!-- Bookings Chart -->
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
          <div class="mb-6 flex items-start justify-between">
            <div class="space-y-1">
              <h2 class="text-lg font-semibold text-[#222222]">Réservations</h2>
              <p class="text-sm text-gray-600">Évolution mensuelle</p>
            </div>
            <div class="rounded-lg bg-blue-50 px-3 py-1">
              <p class="text-2xl font-bold text-blue-600">{{ latestSeriesValue(bookingSeries) }}</p>
              <p class="text-xs text-blue-600">Ce mois</p>
            </div>
          </div>
          <div class="flex h-32 items-end justify-center">
            <svg width="100%" height="128" viewBox="0 0 400 128" class="w-full">
              <defs>
                <linearGradient id="bookingGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" style="stop-color: rgb(59, 130, 246); stop-opacity: 0.5" />
                  <stop offset="100%" style="stop-color: rgb(59, 130, 246); stop-opacity: 0" />
                </linearGradient>
              </defs>
              <polyline
                :points="sparklinePoints(bookingSeries) + ' 400,128 0,128'"
                fill="url(#bookingGradient)"
              />
              <polyline
                :points="sparklinePoints(bookingSeries)"
                fill="none"
                stroke="rgb(59, 130, 246)"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
        </div>

        <!-- Revenue Chart -->
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
          <div class="mb-6 flex items-start justify-between">
            <div class="space-y-1">
              <h2 class="text-lg font-semibold text-[#222222]">Revenus attendus</h2>
              <p class="text-sm text-gray-600">Évolution mensuelle</p>
            </div>
            <div class="rounded-lg bg-green-50 px-3 py-1">
              <p class="text-2xl font-bold text-green-600">
                {{ formatAmount(latestSeriesValue(payoutSeries)) }}
              </p>
              <p class="text-xs text-green-600">Ce mois</p>
            </div>
          </div>
          <div class="flex h-32 items-end justify-center">
            <svg width="100%" height="128" viewBox="0 0 400 128" class="w-full">
              <defs>
                <linearGradient id="revenueGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                  <stop offset="0%" style="stop-color: rgb(34, 197, 94); stop-opacity: 0.5" />
                  <stop offset="100%" style="stop-color: rgb(34, 197, 94); stop-opacity: 0" />
                </linearGradient>
              </defs>
              <polyline
                :points="sparklinePoints(payoutSeries) + ' 400,128 0,128'"
                fill="url(#revenueGradient)"
              />
              <polyline
                :points="sparklinePoints(payoutSeries)"
                fill="none"
                stroke="rgb(34, 197, 94)"
                stroke-width="3"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
            </svg>
          </div>
        </div>
      </div>

      <!-- Booking Calendar -->
      <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
          <div>
            <h2 class="text-lg font-semibold text-[#222222]">Calendrier des réservations</h2>
            <p class="text-sm text-gray-600">Jours occupés pour vos annonces</p>
          </div>
          <div class="flex items-center gap-3">
            <button
              type="button"
              class="rounded-full border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-600 transition hover:border-gray-400 hover:text-[#222222]"
              @click="prevMonth"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 19l-7-7 7-7"
                />
              </svg>
            </button>
            <span class="text-sm font-semibold text-gray-700 capitalize">{{ calendarLabel }}</span>
            <button
              type="button"
              class="rounded-full border border-gray-200 px-3 py-2 text-sm font-semibold text-gray-600 transition hover:border-gray-400 hover:text-[#222222]"
              @click="nextMonth"
            >
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 5l7 7-7 7"
                />
              </svg>
            </button>
          </div>
        </div>

        <div class="grid grid-cols-7 gap-2 text-xs font-semibold text-gray-500">
          <div class="text-center">Lun</div>
          <div class="text-center">Mar</div>
          <div class="text-center">Mer</div>
          <div class="text-center">Jeu</div>
          <div class="text-center">Ven</div>
          <div class="text-center">Sam</div>
          <div class="text-center">Dim</div>
        </div>

        <div class="mt-3 grid grid-cols-7 gap-2 text-sm">
          <div
            v-for="(day, index) in calendarDays"
            :key="index"
            class="group relative flex h-10 items-center justify-center rounded-lg border border-transparent text-gray-500"
            :class="day.iso && bookingDays.has(day.iso) ? 'bg-rose-50 text-rose-700 border-rose-100 font-semibold cursor-pointer' : 'text-gray-700'"
            @click="day.iso && bookingsByDay.get(day.iso)?.length ? openBooking(bookingsByDay.get(day.iso)?.[0]?.id ?? 0) : undefined"
          >
            <span v-if="day.date">{{ day.date.getUTCDate() }}</span>

            <div
              v-if="day.iso && bookingsByDay.get(day.iso)?.length"
              class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-3 w-64 -translate-x-1/2 rounded-xl border border-gray-200 bg-white p-3 text-left text-xs text-gray-700 opacity-0 shadow-lg transition-opacity group-hover:opacity-100"
            >
              <p class="mb-2 text-xs font-semibold text-gray-500">Réservations</p>
              <ul class="space-y-2">
                <li
                  v-for="booking in bookingsByDay.get(day.iso) ?? []"
                  :key="booking.id"
                  class="pointer-events-auto rounded-lg border border-gray-100 px-2 py-1 transition hover:border-rose-200 hover:bg-rose-50 hover:text-rose-700"
                  @click.stop="openBooking(booking.id)"
                >
                  <p class="font-semibold text-gray-800">
                    {{ booking.listing?.title ?? `Annonce #${booking.listing_id}` }}
                  </p>
                  <p class="text-[11px] text-gray-500">
                    {{ booking.guest?.name ?? `Voyageur #${booking.guest_user_id}` }}
                  </p>
                </li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <!-- Activity Section -->
      <div class="grid gap-6 lg:grid-cols-5">
        <!-- Recent Bookings -->
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm lg:col-span-3">
          <div class="mb-6 flex items-center justify-between">
            <div>
              <h2 class="text-lg font-semibold text-[#222222]">Dernières demandes</h2>
              <p class="mt-1 text-sm text-gray-600">Les réservations les plus récentes</p>
            </div>
            <RouterLink
              to="/host/bookings"
              class="text-sm font-medium text-gray-600 underline transition hover:text-[#222222]"
            >
              Voir tout
            </RouterLink>
          </div>

          <EmptyState
            v-if="recentBookings.length === 0"
            title="Aucune réservation"
            subtitle="Les nouvelles demandes apparaîtront ici"
            icon="bookings"
          />

          <div v-else class="space-y-3">
            <RouterLink
              v-for="booking in recentBookings"
              :key="booking.id"
              :to="`/host/bookings/${booking.id}`"
              class="group block rounded-xl border border-gray-100 bg-gray-50 p-4 transition hover:border-gray-200 hover:bg-white hover:shadow-sm"
            >
              <div class="flex items-start justify-between gap-3">
                <div class="flex-1 space-y-1">
                  <p class="font-semibold text-[#222222]">{{ listingLabel(booking.listing_id) }}</p>
                  <p class="text-sm text-gray-600">
                    Du {{ booking.start_date }} au {{ booking.end_date }}
                  </p>
                </div>
                <svg class="h-5 w-5 text-gray-400 transition group-hover:text-gray-600" fill="none" viewBox="0 0 24 24">
                  <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
              </div>
            </RouterLink>
          </div>
        </div>

        <!-- Upcoming Arrivals -->
        <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm lg:col-span-2">
          <div class="mb-6">
            <h2 class="text-lg font-semibold text-[#222222]">Prochaines arrivées</h2>
            <p class="mt-1 text-sm text-gray-600">Réservations confirmées</p>
          </div>

          <EmptyState
            v-if="upcomingArrivals.length === 0"
            title="Aucune arrivée"
            subtitle="Programmée pour le moment"
            icon="calendar"
          />

          <div v-else class="space-y-3">
            <div
              v-for="booking in upcomingArrivals"
              :key="booking.id"
              class="rounded-xl border border-gray-100 bg-gray-50 p-4 transition hover:border-gray-200 hover:bg-white hover:shadow-sm"
            >
              <div class="space-y-1">
                <p class="font-semibold text-[#222222]">{{ listingLabel(booking.listing_id) }}</p>
                <div class="flex items-center gap-2 text-sm text-gray-600">
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                  </svg>
                  {{ booking.start_date }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>
