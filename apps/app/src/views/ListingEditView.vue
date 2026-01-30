<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import { fetchListing, updateListing, type Listing, uploadListingImages, reorderListingImages } from '@/services/listings'

const route = useRoute()
const router = useRouter()
const listing = ref<Listing | null>(null)
const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const success = ref<string | null>(null)

const form = ref({
  title: '',
  description: '',
  city: '',
  address: '',
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

const orderedImages = computed(() => {
  const existing = listing.value?.images ?? []
  return [...existing].sort((a, b) => a.position - b.position)
})

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

function moveExistingImage(id: number, direction: 'up' | 'down') {
  if (!listing.value?.images) return
  const imagesList = [...listing.value.images]
  const index = imagesList.findIndex((item) => item.id === id)
  if (index === -1) return
  const nextIndex = direction === 'up' ? index - 1 : index + 1
  if (nextIndex < 0 || nextIndex >= imagesList.length) return
  const [item] = imagesList.splice(index, 1)
  imagesList.splice(nextIndex, 0, item)
  listing.value.images = imagesList.map((image, idx) => ({
    ...image,
    position: idx + 1,
  }))
}

function removeNewImage(id: number) {
  images.value = images.value.filter((item) => item.id !== id)
}

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const id = Number(route.params.id)
    const data = await fetchListing(id)
    listing.value = data
    form.value = {
      title: data.title,
      description: data.description,
      city: data.city,
      address: data.address,
      guest_capacity: data.guest_capacity,
      price_per_night: data.price_per_night,
      rules: data.rules ?? '',
      amenities: data.amenities ?? [],
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger l’annonce.'
  } finally {
    isLoading.value = false
  }
}

async function submit() {
  error.value = null
  success.value = null
  isSubmitting.value = true

  try {
    if (!listing.value) {
      throw new Error('Annonce introuvable.')
    }

    const updated = await updateListing(listing.value.id, {
      title: form.value.title,
      description: form.value.description,
      city: form.value.city,
      address: form.value.address,
      guest_capacity: Number(form.value.guest_capacity),
      price_per_night: Number(form.value.price_per_night),
      rules: form.value.rules || null,
      amenities: form.value.amenities,
    })

    listing.value = updated

    if (images.value.length > 0) {
      await uploadListingImages(listing.value.id, images.value.map((item) => item.file))
      images.value = []
    }

    if (listing.value.images?.length) {
      await reorderListingImages(
        listing.value.id,
        listing.value.images.sort((a, b) => a.position - b.position).map((image) => image.id)
      )
    }

    success.value = 'Annonce mise à jour.'
    await router.push('/host/listings')
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de modifier l’annonce.'
  } finally {
    isSubmitting.value = false
  }
}

onMounted(load)
</script>

<template>
  <section class="space-y-8">
    <header class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Modifier</p>
        <h1 class="text-3xl font-semibold text-slate-900">Mettre à jour l’annonce</h1>
      </div>
      <RouterLink
        v-if="listing"
        :to="`/listings/${listing.id}`"
        class="inline-flex rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
      >
        Voir l’annonce
      </RouterLink>
    </header>

    <div v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ error }}
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Chargement de l’annonce...
    </div>

    <form
      v-else
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
      </div>

      <div class="space-y-3">
        <label class="text-sm font-medium text-slate-700">Images existantes</label>
        <div v-if="orderedImages.length" class="space-y-3">
          <p class="text-xs text-slate-500">Réorganise les images existantes.</p>
          <div class="grid gap-3 md:grid-cols-2">
            <div
              v-for="image in orderedImages"
              :key="image.id"
              class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3"
            >
              <img :src="image.url" class="h-16 w-16 rounded-xl object-cover" />
              <div class="flex-1 text-xs text-slate-500">Position {{ image.position }}</div>
              <div class="flex items-center gap-2">
                <button
                  class="rounded-full border border-slate-200 px-3 py-1 text-xs"
                  type="button"
                  @click="moveExistingImage(image.id, 'up')"
                >
                  ↑
                </button>
                <button
                  class="rounded-full border border-slate-200 px-3 py-1 text-xs"
                  type="button"
                  @click="moveExistingImage(image.id, 'down')"
                >
                  ↓
                </button>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="rounded-2xl border border-dashed border-slate-200 p-4 text-xs text-slate-500">
          Aucune image enregistrée.
        </div>
      </div>

      <div class="space-y-3">
        <label class="text-sm font-medium text-slate-700">Ajouter des images</label>
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
            <input type="file" accept="image/*" multiple class="hidden" @change="handleFiles" />
          </label>
        </div>
        <div v-if="images.length" class="space-y-3">
          <p class="text-xs text-slate-500">Images en attente d’upload.</p>
          <div class="grid gap-3 md:grid-cols-2">
            <div
              v-for="image in images"
              :key="image.id"
              class="flex items-center gap-3 rounded-2xl border border-slate-200 bg-white p-3"
            >
              <img :src="image.preview" class="h-16 w-16 rounded-xl object-cover" />
              <div class="flex-1 text-xs text-slate-500">Nouvelle image</div>
              <button
                class="rounded-full border border-rose-200 px-3 py-1 text-xs text-rose-600"
                type="button"
                @click="removeNewImage(image.id)"
              >
                Supprimer
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="success" class="rounded-xl bg-emerald-50 px-3 py-2 text-sm text-emerald-600">
        {{ success }}
      </div>

      <button
        class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="isSubmitting"
        type="submit"
      >
        {{ isSubmitting ? 'Mise à jour...' : 'Mettre à jour l’annonce' }}
      </button>
    </form>
  </section>
</template>
