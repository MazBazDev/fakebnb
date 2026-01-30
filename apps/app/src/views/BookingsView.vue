<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { createBooking, fetchBookings, type Booking } from '@/services/bookings'
import { fetchListings, type Listing } from '@/services/listings'

const bookings = ref<Booking[]>([])
const listings = ref<Listing[]>([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const success = ref<string | null>(null)
const form = ref({
  listing_id: '',
  start_date: '',
  end_date: '',
})

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const [bookingsData, listingsData] = await Promise.all([fetchBookings(), fetchListings()])
    bookings.value = bookingsData
    listings.value = listingsData
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les réservations.'
  } finally {
    isLoading.value = false
  }
}

async function submit() {
  error.value = null
  success.value = null
  isSubmitting.value = true

  try {
    const booking = await createBooking({
      listing_id: Number(form.value.listing_id),
      start_date: form.value.start_date,
      end_date: form.value.end_date,
    })
    bookings.value = [booking, ...bookings.value]
    success.value = 'Réservation confirmée.'
    form.value = { listing_id: '', start_date: '', end_date: '' }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de réserver.'
  } finally {
    isSubmitting.value = false
  }
}

function listingLabel(listingId: number) {
  const listing = listings.value.find((item) => item.id === listingId)
  return listing ? `${listing.title} — ${listing.city}` : `Annonce #${listingId}`
}

onMounted(load)
</script>

<template>
  <section class="space-y-8">
    <header class="space-y-2">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Réservations</p>
      <h1 class="text-3xl font-semibold text-slate-900">Planifier un séjour</h1>
      <p class="text-sm text-slate-500">
        Réserve une annonce disponible et retrouve l’historique ici.
      </p>
    </header>

    <form
      class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
      @submit.prevent="submit"
    >
      <div class="grid gap-4 md:grid-cols-3">
        <div class="space-y-2 md:col-span-2">
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
          <label class="text-sm font-medium text-slate-700">Arrivée</label>
          <input
            v-model="form.start_date"
            type="date"
            class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
            required
          />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Départ</label>
          <input
            v-model="form.end_date"
            type="date"
            class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
            required
          />
        </div>
      </div>

      <div v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
        {{ error }}
      </div>
      <div v-if="success" class="rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-600">
        {{ success }}
      </div>

      <button
        class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="isSubmitting"
        type="submit"
      >
        {{ isSubmitting ? 'Réservation...' : 'Réserver' }}
      </button>
    </form>

    <div class="space-y-3">
      <h2 class="text-sm font-semibold text-slate-700">Historique</h2>
      <div
        v-if="isLoading"
        class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
      >
        Chargement des réservations...
      </div>
      <div
        v-else-if="bookings.length === 0"
        class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-sm text-slate-500"
      >
        Aucune réservation enregistrée.
      </div>
      <div v-else class="space-y-2">
        <article
          v-for="booking in bookings"
          :key="booking.id"
          class="rounded-2xl border border-slate-200 bg-white p-4 text-sm text-slate-600"
        >
          <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="font-semibold text-slate-800">
              {{ listingLabel(booking.listing_id) }}
            </span>
            <span class="text-xs uppercase tracking-[0.2em] text-slate-400">
              #{{ booking.id }}
            </span>
          </div>
          <p class="mt-2">
            Du {{ booking.start_date }} au {{ booking.end_date }}
          </p>
        </article>
      </div>
    </div>
  </section>
</template>
