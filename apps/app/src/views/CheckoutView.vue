<script setup lang="ts">
import { computed, onMounted } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { fetchBookings, type Booking } from '@/services/bookings'
import { createPaymentIntent, authorizePayment } from '@/services/payments'
import { useAsyncData, useFormSubmit } from '@/composables'
import { PageHeader, LoadingSpinner, AlertMessage } from '@/components/ui'

const route = useRoute()
const router = useRouter()

// Data fetching
const {
  data: booking,
  isLoading,
  error: loadError,
  execute: load,
} = useAsyncData<Booking | null>(
  async () => {
    const id = Number(route.params.bookingId)
    const bookings = await fetchBookings()
    const found = bookings.find((item) => item.id === id) ?? null

    if (!found) {
      throw new Error('Réservation introuvable.')
    }

    if (found.status !== 'awaiting_payment') {
      throw new Error('Cette réservation ne nécessite pas de paiement.')
    }

    if (!found.payment) {
      const intent = await createPaymentIntent(found.id)
      found.payment = intent.data
    }

    return found
  },
  {
    defaultValue: null,
    errorMessage: 'Impossible de charger le checkout.',
  }
)

// Payment submission
const { isSubmitting, error: payError, submit: pay } = useFormSubmit(
  async () => {
    if (!booking.value) {
      throw new Error('Réservation introuvable.')
    }

    const intent = await createPaymentIntent(booking.value.id)
    await authorizePayment(intent.data.id)
    await router.push('/bookings')
  },
  { errorMessage: 'Paiement impossible.' }
)

// Computed
const breadcrumbs = computed(() => [
  { label: 'Mes réservations', to: '/bookings' },
  { label: booking.value ? `Paiement #${booking.value.id}` : 'Paiement' },
])

const pageTitle = computed(() =>
  booking.value ? `Paiement de la réservation #${booking.value.id}` : 'Paiement de la réservation'
)

onMounted(load)
</script>

<template>
  <section class="mx-auto max-w-2xl space-y-8">
    <PageHeader
      :title="pageTitle"
      subtitle="Simulation de paiement sécurisé"
      :breadcrumbs="breadcrumbs"
    />

    <!-- Error Messages -->
    <AlertMessage v-if="loadError" :message="loadError" type="error" />
    <AlertMessage v-if="payError" :message="payError" type="error" />

    <!-- Loading State -->
    <LoadingSpinner v-if="isLoading" text="Chargement du checkout..." full-container />

    <!-- Checkout Card -->
    <div
      v-else-if="booking"
      class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
    >
      <!-- Header -->
      <div class="flex flex-wrap items-center justify-between gap-4 border-b border-gray-100 pb-6">
        <div>
          <p class="text-sm font-medium text-gray-500">Réservation #{{ booking.id }}</p>
          <p class="mt-1 text-lg font-semibold text-[#222222]">
            Du {{ booking.start_date }} au {{ booking.end_date }}
          </p>
        </div>
        <RouterLink
          to="/bookings"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 19l-7-7 7-7"
            />
          </svg>
          Retour
        </RouterLink>
      </div>

      <!-- Price Breakdown -->
      <div class="space-y-4">
        <h2 class="text-sm font-semibold text-[#222222]">Détail du paiement</h2>

        <div class="space-y-3 rounded-xl bg-gray-50 p-4">
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Montant de base</span>
            <span class="font-medium text-[#222222]">
              {{ booking.payment?.amount_base ?? '—' }} €
            </span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">TVA (20%)</span>
            <span class="font-medium text-[#222222]">
              {{ booking.payment?.amount_vat ?? '—' }} €
            </span>
          </div>
          <div class="flex items-center justify-between text-sm">
            <span class="text-gray-600">Frais de service (7%)</span>
            <span class="font-medium text-[#222222]">
              {{ booking.payment?.amount_service ?? '—' }} €
            </span>
          </div>
          <div class="border-t border-gray-200 pt-3">
            <div class="flex items-center justify-between">
              <span class="text-base font-semibold text-[#222222]">Total TTC</span>
              <span class="text-xl font-bold text-[#222222]">
                {{ booking.payment?.amount_total ?? '—' }} €
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Notice -->
      <div class="flex items-start gap-3 rounded-xl bg-amber-50 p-4">
        <svg
          class="h-5 w-5 shrink-0 text-amber-600"
          fill="none"
          viewBox="0 0 24 24"
          aria-hidden="true"
        >
          <path
            stroke="currentColor"
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        <p class="text-sm text-amber-800">
          Ceci est une simulation de paiement. Aucune transaction réelle ne sera effectuée.
        </p>
      </div>

      <!-- Pay Button -->
      <button
        class="w-full rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="isSubmitting"
        type="button"
        @click="pay"
      >
        <span v-if="isSubmitting" class="inline-flex items-center gap-2">
          <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle
              class="opacity-25"
              cx="12"
              cy="12"
              r="10"
              stroke="currentColor"
              stroke-width="4"
            ></circle>
            <path
              class="opacity-75"
              fill="currentColor"
              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
          </svg>
          Paiement en cours...
        </span>
        <span v-else class="inline-flex items-center gap-2">
          <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
            />
          </svg>
          Payer {{ booking.payment?.amount_total ?? '—' }} €
        </span>
      </button>
    </div>
  </section>
</template>
