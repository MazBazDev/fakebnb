<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useRoute, RouterLink, useRouter } from 'vue-router'
import { fetchListing, type Listing } from '@/services/listings'
import { createBooking } from '@/services/bookings'
import { createConversation } from '@/services/conversations'

const route = useRoute()
const router = useRouter()
const listing = ref<Listing | null>(null)
const isLoading = ref(false)
const error = ref<string | null>(null)
const bookingError = ref<string | null>(null)
const bookingSuccess = ref<string | null>(null)
const isSubmitting = ref(false)
const messageError = ref<string | null>(null)
const isMessaging = ref(false)
const bookingForm = ref({
  start_date: '',
  end_date: '',
})
const amenityLabels: Record<string, string> = {
  wifi: 'Wi-Fi',
  kitchen: 'Cuisine',
  parking: 'Parking gratuit',
  washer: 'Lave-linge',
  tv: 'TV',
  air_conditioning: 'Climatisation',
  heating: 'Chauffage',
  workspace: 'Espace de travail',
  pool: 'Piscine',
  hot_tub: 'Jacuzzi',
}

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const id = Number(route.params.id)
    listing.value = await fetchListing(id)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger l’annonce.'
  } finally {
    isLoading.value = false
  }
}

onMounted(load)

async function submitBooking() {
  bookingError.value = null
  bookingSuccess.value = null
  isSubmitting.value = true

  try {
    if (!listing.value) {
      throw new Error('Annonce introuvable.')
    }

    await createBooking({
      listing_id: listing.value.id,
      start_date: bookingForm.value.start_date,
      end_date: bookingForm.value.end_date,
    })
    bookingSuccess.value = 'Réservation confirmée.'
    bookingForm.value = { start_date: '', end_date: '' }
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
    <RouterLink to="/listings" class="text-sm font-semibold text-slate-600 hover:text-slate-900">
      ← Retour aux annonces
    </RouterLink>

    <div v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ error }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Chargement de l’annonce...
    </div>

    <div v-else-if="listing" class="space-y-6">
      <header class="space-y-2">
        <p class="text-sm uppercase tracking-[0.2em] text-slate-500">{{ listing.city }}</p>
        <h1 class="text-3xl font-semibold text-slate-900">{{ listing.title }}</h1>
        <p class="text-sm text-slate-500">{{ listing.address }}</p>
      </header>

      <div v-if="listing.images?.length" class="overflow-hidden rounded-3xl border border-slate-200">
        <div class="flex gap-3 overflow-x-auto bg-white p-3">
          <img
            v-for="image in listing.images"
            :key="image.id"
            :src="image.url"
            class="h-56 w-72 flex-shrink-0 rounded-2xl object-cover"
            alt=""
          />
        </div>
      </div>

      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-4">
          <p class="text-lg font-semibold text-slate-900">{{ listing.price_per_night }} €/nuit</p>
          <span class="text-xs font-semibold text-slate-500">
            Capacité {{ listing.guest_capacity }} personne{{ listing.guest_capacity > 1 ? 's' : '' }}
          </span>
          <span class="text-xs uppercase tracking-[0.2em] text-slate-400">
            Id #{{ listing.id }}
          </span>
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
            {{ amenityLabels[amenity] ?? amenity.replace('_', ' ') }}
          </span>
        </div>

        <div v-if="listing.rules" class="mt-6 rounded-xl bg-slate-50 p-4 text-sm text-slate-600">
          <p class="text-xs font-semibold uppercase tracking-[0.2em] text-slate-400">Règles</p>
          <p class="mt-2">{{ listing.rules }}</p>
        </div>

        <div class="mt-6 flex flex-wrap items-center gap-3">
          <button
            class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
            type="button"
            :disabled="isMessaging"
            @click="contactHost"
          >
            {{ isMessaging ? 'Ouverture...' : 'Contacter l’hôte' }}
          </button>
          <p v-if="messageError" class="text-xs text-rose-600">{{ messageError }}</p>
        </div>
      </div>

      <form
        class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
        @submit.prevent="submitBooking"
      >
        <h2 class="text-sm font-semibold text-slate-700">Réserver ce logement</h2>
        <div class="grid gap-4 md:grid-cols-2">
          <div class="space-y-2">
            <label class="text-sm font-medium text-slate-700">Arrivée</label>
            <input
              v-model="bookingForm.start_date"
              type="date"
              class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
              required
            />
          </div>
          <div class="space-y-2">
            <label class="text-sm font-medium text-slate-700">Départ</label>
            <input
              v-model="bookingForm.end_date"
              type="date"
              class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
              required
            />
          </div>
        </div>

        <div
          v-if="bookingError"
          class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600"
        >
          {{ bookingError }}
        </div>
        <div
          v-if="bookingSuccess"
          class="rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-600"
        >
          {{ bookingSuccess }}
        </div>

        <button
          class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="isSubmitting"
          type="submit"
        >
          {{ isSubmitting ? 'Réservation...' : 'Réserver' }}
        </button>
      </form>
    </div>
  </section>
</template>
