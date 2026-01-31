<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { createListing, uploadListingImages } from '@/services/listings'

const router = useRouter()
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const success = ref<string | null>(null)
const form = ref({
  title: '',
  description: '',
  city: '',
  address: '',
  full_address: '',
  guest_capacity: 1,
  price_per_night: 0,
  rules: '',
  amenities: [] as string[],
})
const images = ref<{ id: number; file: File; preview: string }[]>([])
let nextImageId = 0
const isDragActive = ref(false)
const amenityOptions = [
  { id: 'wifi', label: 'Wi-Fi' },
  { id: 'kitchen', label: 'Cuisine' },
  { id: 'parking', label: 'Parking gratuit' },
  { id: 'washer', label: 'Lave-linge' },
  { id: 'tv', label: 'TV' },
  { id: 'air_conditioning', label: 'Climatisation' },
  { id: 'heating', label: 'Chauffage' },
  { id: 'workspace', label: 'Espace de travail' },
  { id: 'pool', label: 'Piscine' },
  { id: 'hot_tub', label: 'Jacuzzi' },
]

const orderedImages = computed(() => images.value)

function appendFiles(files: File[]) {
  files.forEach((file) => {
    images.value.push({
      id: nextImageId++,
      file,
      preview: URL.createObjectURL(file),
    })
  })
}

function handleFiles(event: Event) {
  const input = event.target as HTMLInputElement
  if (!input.files) return
  appendFiles(Array.from(input.files))
  input.value = ''
}

function handleDrop(event: DragEvent) {
  event.preventDefault()
  isDragActive.value = false
  const files = Array.from(event.dataTransfer?.files ?? [])
  if (files.length) {
    appendFiles(files)
  }
}

function handleDragOver(event: DragEvent) {
  event.preventDefault()
  isDragActive.value = true
}

function handleDragLeave() {
  isDragActive.value = false
}

function moveImage(id: number, direction: 'up' | 'down') {
  const index = images.value.findIndex((item) => item.id === id)
  if (index === -1) return
  const nextIndex = direction === 'up' ? index - 1 : index + 1
  if (nextIndex < 0 || nextIndex >= images.value.length) return
  const [item] = images.value.splice(index, 1)
  images.value.splice(nextIndex, 0, item)
}

function removeImage(id: number) {
  images.value = images.value.filter((item) => item.id !== id)
}

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
      full_address: form.value.full_address || null,
      guest_capacity: Number(form.value.guest_capacity),
      price_per_night: Number(form.value.price_per_night),
      rules: form.value.rules || null,
      amenities: form.value.amenities,
    })
    if (images.value.length > 0) {
      await uploadListingImages(
        listing.id,
        images.value.map((item) => item.file)
      )
    }
    success.value = 'Annonce publiée.'
    await router.push(`/host/listings/${listing.id}/edit`)
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
        <label class="text-sm font-medium text-slate-700">Adresse complète</label>
        <input
          v-model="form.full_address"
          type="text"
          class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
          placeholder="10 Rue de Rivoli, 75001 Paris, France"
        />
        <p class="text-xs text-slate-500">
          Utilisée pour placer le logement sur la carte.
        </p>
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

      <div class="grid gap-4 md:grid-cols-3">
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
          <label class="text-sm font-medium text-slate-700">Capacité (personnes)</label>
          <input
            v-model.number="form.guest_capacity"
            type="number"
            min="1"
            max="100"
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

      <div class="space-y-3">
        <label class="text-sm font-medium text-slate-700">Équipements</label>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <label
            v-for="amenity in amenityOptions"
            :key="amenity.id"
            class="flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"
          >
            <input
              v-model="form.amenities"
              type="checkbox"
              :value="amenity.id"
              class="h-4 w-4 rounded border-slate-300 text-slate-900"
            />
            <span>{{ amenity.label }}</span>
          </label>
        </div>
        <p class="text-xs text-slate-500">Sélection multiple possible.</p>
      </div>

      <div class="space-y-3">
        <label class="text-sm font-medium text-slate-700">Images</label>
        <div
          class="rounded-2xl border border-dashed px-4 py-6 text-center transition"
          :class="isDragActive ? 'border-slate-900 bg-slate-50' : 'border-slate-200 bg-white'"
          @drop="handleDrop"
          @dragover="handleDragOver"
          @dragleave="handleDragLeave"
        >
          <p class="text-sm font-semibold text-slate-700">Dépose tes images ici</p>
          <p class="mt-1 text-xs text-slate-500">PNG, JPG, WebP — multiple autorisé</p>
          <label class="mt-4 inline-flex cursor-pointer rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700">
            Parcourir
            <input
              type="file"
              accept="image/*"
              multiple
              class="hidden"
              @change="handleFiles"
            />
          </label>
        </div>
        <div v-if="orderedImages.length" class="space-y-3">
          <p class="text-xs text-slate-500">Glisse les images avec les boutons pour l’ordre.</p>
          <div class="grid gap-3 md:grid-cols-2">
            <div
              v-for="image in orderedImages"
              :key="image.id"
              class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3"
            >
              <img :src="image.preview" class="h-16 w-16 rounded-xl object-cover" />
              <div class="flex-1 text-xs text-slate-500">Position {{ orderedImages.indexOf(image) + 1 }}</div>
              <div class="flex items-center gap-2">
                <button
                  class="rounded-full border border-slate-200 px-3 py-1 text-xs"
                  type="button"
                  @click="moveImage(image.id, 'up')"
                >
                  ↑
                </button>
                <button
                  class="rounded-full border border-slate-200 px-3 py-1 text-xs"
                  type="button"
                  @click="moveImage(image.id, 'down')"
                >
                  ↓
                </button>
                <button
                  class="rounded-full border border-rose-200 px-3 py-1 text-xs text-rose-600"
                  type="button"
                  @click="removeImage(image.id)"
                >
                  Supprimer
                </button>
              </div>
            </div>
          </div>
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
