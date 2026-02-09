<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { fetchBookings, type Booking } from '@/services/bookings'
import { createPaymentIntent, authorizePayment } from '@/services/payments'
import { useAsyncData, useFormSubmit } from '@/composables'
import { PageHeader, LoadingSpinner, AlertMessage } from '@/components/ui'

const route = useRoute()
const router = useRouter()
const cashoulaState = ref<'idle' | 'spinning' | 'revealed'>('idle')
const spinRotation = ref(0)

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

    if (shouldShowCashoula.value && cashoulaState.value !== 'revealed') {
      throw new Error('Lancez la roue Cashoula direct avant de payer.')
    }

    const paymentId = booking.value.payment?.id
    if (!paymentId) {
      const intent = await createPaymentIntent(booking.value.id)
      booking.value.payment = intent.data
      await authorizePayment(intent.data.id)
    } else {
      await authorizePayment(paymentId)
    }
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

const shouldShowCashoula = computed(
  () => Boolean(booking.value?.listing?.cashoula_direct_enabled)
)

const cashoulaWon = computed(
  () => Boolean(booking.value?.payment?.cashoula_direct_won)
)

const cashoulaLabel = computed(() => {
  if (cashoulaState.value === 'spinning') return 'La roue tourne...'
  if (cashoulaState.value !== 'revealed') return 'Tente ta chance'
  return cashoulaWon.value ? 'Gagné : réservation offerte !' : 'Perdu : paiement standard'
})

async function spinCashoula() {
  if (!booking.value) return
  if (cashoulaState.value === 'spinning') return

  cashoulaState.value = 'spinning'

  if (!booking.value.payment) {
    const intent = await createPaymentIntent(booking.value.id)
    booking.value.payment = intent.data
  }

  const baseAngle = cashoulaWon.value ? 45 : 225
  const jitter = Math.floor(Math.random() * 30) - 15
  const extraSpins = 6 + Math.floor(Math.random() * 3)
  spinRotation.value = 360 * extraSpins + baseAngle + jitter

  await new Promise((resolve) => window.setTimeout(resolve, 1800))
  cashoulaState.value = 'revealed'
  await new Promise((resolve) => window.setTimeout(resolve, 150))
}

onMounted(load)

watch(
  () => booking.value?.payment?.cashoula_direct_applied,
  (applied) => {
    if (!applied || cashoulaState.value !== 'idle') return
    const baseAngle = cashoulaWon.value ? 45 : 225
    spinRotation.value = baseAngle
    cashoulaState.value = 'revealed'
  },
  { immediate: true }
)
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

      <div v-if="shouldShowCashoula" class="cashoula-card">
        <div class="cashoula-header">
          <div>
            <p class="cashoula-title">Cashoula direct</p>
            <p class="cashoula-subtitle">
              Une roue 50/50 peut rendre ta réservation gratuite. Le résultat est décidé côté serveur.
            </p>
          </div>
          <span class="cashoula-badge">50/50</span>
        </div>

        <div class="cashoula-wheel-wrap">
          <div class="cashoula-wheel">
            <div class="cashoula-pointer"></div>
            <div
              class="cashoula-dial"
              :style="{
                transform: `rotate(${spinRotation}deg)`,
              }"
            ></div>
            <div class="cashoula-center">
              <span>{{ cashoulaLabel }}</span>
            </div>
          </div>
          <p class="cashoula-state">{{ cashoulaLabel }}</p>
          <button
            class="cashoula-action"
            type="button"
            :disabled="cashoulaState === 'spinning' || cashoulaState === 'revealed'"
            @click="spinCashoula"
          >
            {{
              cashoulaState === 'revealed'
                ? 'Résultat verrouillé'
                : cashoulaState === 'spinning'
                  ? 'Roulette en cours...'
                  : 'Lancer la roue'
            }}
          </button>
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
        :disabled="isSubmitting || (shouldShowCashoula && cashoulaState !== 'revealed')"
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
          {{
            booking.payment?.amount_total === 0
              ? 'Finaliser la réservation'
              : `Payer ${booking.payment?.amount_total ?? '—'} €`
          }}
        </span>
      </button>
    </div>
  </section>
</template>

<style scoped>
.cashoula-card {
  border-radius: 24px;
  border: 1px solid color-mix(in srgb, var(--color-brand-primary) 20%, transparent);
  background: linear-gradient(
      135deg,
      color-mix(in srgb, var(--color-brand-primary) 16%, transparent) 0%,
      color-mix(in srgb, var(--color-bg-primary) 100%, transparent) 60%
    ),
    var(--color-bg-elevated);
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 18px;
}

.cashoula-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
}

.cashoula-title {
  font-size: 0.95rem;
  font-weight: 700;
  color: var(--color-text-primary);
}

.cashoula-subtitle {
  margin-top: 6px;
  font-size: 0.75rem;
  color: var(--color-text-secondary);
  max-width: 360px;
}

.cashoula-badge {
  border-radius: 999px;
  padding: 6px 12px;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--color-text-primary);
  background: var(--color-bg-primary);
  border: 1px solid var(--color-border-secondary);
  box-shadow: 0 8px 18px rgba(0, 0, 0, 0.08);
}

.cashoula-wheel-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}

.cashoula-wheel {
  position: relative;
  width: 210px;
  height: 210px;
  display: grid;
  place-items: center;
}

.cashoula-pointer {
  position: absolute;
  top: -8px;
  left: 50%;
  transform: translateX(-50%);
  width: 0;
  height: 0;
  border-left: 10px solid transparent;
  border-right: 10px solid transparent;
  border-bottom: 16px solid var(--color-brand-primary);
  filter: drop-shadow(0 6px 12px rgba(0, 0, 0, 0.18));
  z-index: 4;
}

.cashoula-dial {
  width: 100%;
  height: 100%;
  border-radius: 999px;
  border: 8px solid var(--color-bg-primary);
  background: conic-gradient(
    color-mix(in srgb, var(--color-success) 80%, #ffffff) 0deg 180deg,
    color-mix(in srgb, var(--color-brand-primary) 80%, #ffffff) 180deg 360deg
  );
  box-shadow: inset 0 0 30px rgba(0, 0, 0, 0.15), 0 18px 45px rgba(0, 0, 0, 0.18);
  transition: transform 1200ms cubic-bezier(0.2, 0.7, 0.2, 1);
}

.cashoula-center {
  position: absolute;
  inset: 36px;
  border-radius: 999px;
  background: color-mix(in srgb, var(--color-bg-primary) 90%, transparent);
  border: 1px solid var(--color-border-secondary);
  display: grid;
  place-items: center;
  text-align: center;
  font-size: 0.7rem;
  font-weight: 700;
  color: var(--color-text-primary);
  padding: 0 14px;
  z-index: 3;
}

.cashoula-state {
  font-size: 0.9rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.cashoula-action {
  border-radius: 999px;
  border: 1px solid var(--color-border-secondary);
  padding: 10px 18px;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--color-brand-primary);
  background: var(--color-bg-primary);
  transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
}

.cashoula-action:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 12px 24px rgba(0, 0, 0, 0.12);
  border-color: var(--color-brand-primary);
}

.cashoula-action:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

@media (max-width: 640px) {
  .cashoula-wheel {
    width: 180px;
    height: 180px;
  }

  .cashoula-center {
    inset: 30px;
  }
}
</style>
