<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { fetchBookings, type Booking } from '@/services/bookings'
import { createPaymentIntent, authorizePayment } from '@/services/payments'

const route = useRoute()
const router = useRouter()
const booking = ref<Booking | null>(null)
const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref<string | null>(null)

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const id = Number(route.params.bookingId)
    const bookings = await fetchBookings()
    booking.value = bookings.find((item) => item.id === id) ?? null

    if (!booking.value) {
      throw new Error('Réservation introuvable.')
    }

    if (booking.value.status !== 'awaiting_payment') {
      throw new Error('Cette réservation ne nécessite pas de paiement.')
    }

    if (!booking.value.payment) {
      const intent = await createPaymentIntent(booking.value.id)
      booking.value.payment = intent.data
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger le checkout.'
  } finally {
    isLoading.value = false
  }
}

async function pay() {
  if (!booking.value) return
  isSubmitting.value = true
  error.value = null

  try {
    const intent = await createPaymentIntent(booking.value.id)
    await authorizePayment(intent.data.id)
    await router.push('/bookings')
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Paiement impossible.'
  } finally {
    isSubmitting.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="space-y-8">
    <header class="space-y-2">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Checkout</p>
      <h1 class="text-3xl font-semibold text-slate-900">Paiement de la réservation</h1>
      <p class="text-sm text-slate-500">Simulation de paiement (fake).</p>
    </header>

    <div v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ error }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Chargement du checkout...
    </div>

    <div v-else-if="booking" class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
          <p class="text-xs uppercase tracking-[0.2em] text-slate-400">Réservation #{{ booking.id }}</p>
          <p class="text-sm text-slate-500">
            Du {{ booking.start_date }} au {{ booking.end_date }}
          </p>
        </div>
        <RouterLink
          to="/bookings"
          class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
        >
          Retour
        </RouterLink>
      </div>

      <div class="mt-6 space-y-2 text-sm text-slate-600">
        <p>
          Total TTC :
          <strong class="text-slate-900">{{ booking.payment?.amount_total ?? '—' }} €</strong>
        </p>
        <p>Base : {{ booking.payment?.amount_base ?? '—' }} €</p>
        <p>TVA (20%) : {{ booking.payment?.amount_vat ?? '—' }} €</p>
        <p>Frais service (7%) : {{ booking.payment?.amount_service ?? '—' }} €</p>
      </div>

      <button
        class="mt-6 w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="isSubmitting"
        type="button"
        @click="pay"
      >
        {{ isSubmitting ? 'Paiement...' : 'Payer maintenant' }}
      </button>
    </div>
  </section>
</template>
