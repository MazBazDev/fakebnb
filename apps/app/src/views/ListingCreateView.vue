<script setup lang="ts">
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { createListing } from '@/services/listings'

const router = useRouter()
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const success = ref<string | null>(null)
const form = ref({
  title: '',
  description: '',
  city: '',
  address: '',
  price_per_night: 0,
  rules: '',
})

async function submit() {
  error.value = null
  success.value = null
  isSubmitting.value = true

  try {
    const listing = await createListing({
      title: form.value.title,
      description: form.value.description,
      city: form.value.city,
      address: form.value.address,
      price_per_night: Number(form.value.price_per_night),
      rules: form.value.rules || null,
    })
    success.value = 'Annonce publiée.'
    await router.push(`/listings/${listing.id}`)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de créer l’annonce.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="space-y-8">
    <header class="space-y-2">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Nouvelle annonce</p>
      <h1 class="text-3xl font-semibold text-slate-900">Publier un logement</h1>
      <p class="text-sm text-slate-500">
        Crée une annonce pour commencer à recevoir des réservations.
      </p>
    </header>

    <form
      class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
      @submit.prevent="submit"
    >
      <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Titre</label>
          <input
            v-model="form.title"
            type="text"
            class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
            required
          />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Ville</label>
          <input
            v-model="form.city"
            type="text"
            class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
            required
          />
        </div>
      </div>

      <div class="space-y-2">
        <label class="text-sm font-medium text-slate-700">Adresse</label>
        <input
          v-model="form.address"
          type="text"
          class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
          required
        />
      </div>

      <div class="space-y-2">
        <label class="text-sm font-medium text-slate-700">Description</label>
        <textarea
          v-model="form.description"
          rows="4"
          class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
          required
        ></textarea>
      </div>

      <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Prix par nuit (€)</label>
          <input
            v-model.number="form.price_per_night"
            type="number"
            min="1"
            class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
            required
          />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Règles (optionnel)</label>
          <input
            v-model="form.rules"
            type="text"
            class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
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
        {{ isSubmitting ? 'Publication...' : 'Publier' }}
      </button>
    </form>
  </section>
</template>
