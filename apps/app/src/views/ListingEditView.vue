<script setup lang="ts">
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter, RouterLink } from 'vue-router'
import {
  fetchListing,
  updateListing,
  type Listing,
  uploadListingImages,
  reorderListingImages,
} from '@/services/listings'
import { useAsyncData, useFormSubmit } from '@/composables'
import { AMENITY_OPTIONS } from '@/constants/amenities'
import { PageHeader, LoadingSpinner, AlertMessage } from '@/components/ui'

const route = useRoute()
const router = useRouter()

// Listing data
const listing = ref<Listing | null>(null)

// Form state
const form = ref({
  title: '',
  description: '',
  city: '',
  address: '',
  full_address: '',
  guest_capacity: 1,
  price_per_night: 0,
  cashoula_direct_enabled: false,
  rules: '',
  amenities: [] as string[],
})

// Image handling
const newImages = ref<{ id: number; file: File; preview: string }[]>([])
let nextImageId = 0
const isDragActive = ref(false)
const isReordering = ref(false)

// Data fetching
const { isLoading, error: loadError, execute: load } = useAsyncData(
  async () => {
    const id = Number(route.params.id)
    const data = await fetchListing(id)
    listing.value = data
    form.value = {
      title: data.title,
      description: data.description,
      city: data.city,
      address: data.address,
      full_address: data.full_address ?? '',
      guest_capacity: data.guest_capacity,
      price_per_night: data.price_per_night,
      cashoula_direct_enabled: data.cashoula_direct_enabled ?? false,
      rules: data.rules ?? '',
      amenities: data.amenities ?? [],
    }
    return data
  },
  { errorMessage: "Impossible de charger l'annonce." }
)

// Form submission
const { isSubmitting, error: submitError, success, submit } = useFormSubmit(
  async () => {
    if (!listing.value) {
      throw new Error('Annonce introuvable.')
    }

    const existingImages = listing.value.images ? [...listing.value.images] : []

    const updated = await updateListing(listing.value.id, {
      title: form.value.title,
      description: form.value.description,
      city: form.value.city,
      address: form.value.address,
      full_address: form.value.full_address || null,
      guest_capacity: Number(form.value.guest_capacity),
      price_per_night: Number(form.value.price_per_night),
      cashoula_direct_enabled: form.value.cashoula_direct_enabled,
      rules: form.value.rules || null,
      amenities: form.value.amenities,
    })

    listing.value = { ...updated, images: existingImages }

    if (newImages.value.length > 0) {
      await uploadListingImages(
        listing.value.id,
        newImages.value.map((item) => item.file)
      )
      // Clear uploaded images
      newImages.value.forEach((img) => URL.revokeObjectURL(img.preview))
      newImages.value = []
    }

    // Refresh listing to get updated images
    const refreshed = await fetchListing(listing.value.id)
    listing.value = refreshed

    await router.push('/host/listings')
    return updated
  },
  {
    successMessage: 'Annonce mise à jour avec succès.',
    errorMessage: "Impossible de modifier l'annonce.",
  }
)

// Computed
const orderedExistingImages = computed(() => {
  const images = listing.value?.images ?? []
  return [...images].sort((a, b) => a.position - b.position)
})

const pageTitle = computed(() => listing.value?.title ?? 'Annonce')

const breadcrumbs = computed(() => [
  { label: 'Hôte', to: '/host' },
  { label: 'Annonces', to: '/host/listings' },
  { label: pageTitle.value },
  { label: 'Modifier' },
])

// Image methods
function appendFiles(files: File[]) {
  files.forEach((file) => {
    newImages.value.push({
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

async function moveExistingImage(id: number, direction: 'up' | 'down') {
  if (!listing.value?.images || isReordering.value) return

  const imagesList = [...listing.value.images]
  const index = imagesList.findIndex((item) => item.id === id)
  if (index === -1) return

  const nextIndex = direction === 'up' ? index - 1 : index + 1
  if (nextIndex < 0 || nextIndex >= imagesList.length) return

  const [item] = imagesList.splice(index, 1)
  if (!item) return
  imagesList.splice(nextIndex, 0, item)

  // Update positions locally
  listing.value.images = imagesList.map((image, idx) => ({
    ...image,
    position: idx + 1,
  }))

  // Save to API
  isReordering.value = true
  try {
    const orderedIds = imagesList.map((img) => img.id)
    await reorderListingImages(listing.value.id, orderedIds)
  } catch {
    // Reload to get correct state
    await load()
  } finally {
    isReordering.value = false
  }
}

function removeNewImage(id: number) {
  const image = newImages.value.find((item) => item.id === id)
  if (image) {
    URL.revokeObjectURL(image.preview)
  }
  newImages.value = newImages.value.filter((item) => item.id !== id)
}

onMounted(load)
</script>

<template>
  <section class="space-y-8">
    <PageHeader
      title="Mettre à jour l'annonce"
      :subtitle="pageTitle"
      :breadcrumbs="breadcrumbs"
    >
      <template v-if="listing" #actions>
        <RouterLink
          :to="`/listings/${listing.id}`"
          class="inline-flex items-center gap-2 rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
        >
          <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
            />
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
            />
          </svg>
          Voir l'annonce
        </RouterLink>
      </template>
    </PageHeader>

    <!-- Error Messages -->
    <AlertMessage v-if="loadError" :message="loadError" type="error" />
    <AlertMessage v-if="submitError" :message="submitError" type="error" />

    <!-- Loading State -->
    <LoadingSpinner v-if="isLoading" text="Chargement de l'annonce..." full-container />

    <!-- Form -->
    <form
      v-else-if="listing"
      class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
      @submit.prevent="submit"
    >
      <!-- Title & City -->
      <div class="grid gap-4 md:grid-cols-2">
        <div class="space-y-2">
          <label for="title" class="block text-sm font-semibold text-[#222222]">
            Titre
          </label>
          <input
            id="title"
            v-model="form.title"
            type="text"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
            required
          />
        </div>
        <div class="space-y-2">
          <label for="city" class="block text-sm font-semibold text-[#222222]">
            Ville
          </label>
          <input
            id="city"
            v-model="form.city"
            type="text"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
            required
          />
        </div>
      </div>

      <!-- Address -->
      <div class="space-y-2">
        <label for="address" class="block text-sm font-semibold text-[#222222]">
          Adresse
        </label>
        <input
          id="address"
          v-model="form.address"
          type="text"
          class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
          required
        />
      </div>

      <!-- Full Address -->
      <div class="space-y-2">
        <label for="full_address" class="block text-sm font-semibold text-[#222222]">
          Adresse complète
          <span class="font-normal text-gray-500">(optionnel)</span>
        </label>
        <input
          id="full_address"
          v-model="form.full_address"
          type="text"
          class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
          placeholder="10 Rue de Rivoli, 75001 Paris, France"
        />
        <p class="text-xs text-gray-500">
          Utilisée pour placer le logement sur la carte.
        </p>
      </div>

      <!-- Description -->
      <div class="space-y-2">
        <label for="description" class="block text-sm font-semibold text-[#222222]">
          Description
        </label>
        <textarea
          id="description"
          v-model="form.description"
          rows="4"
          class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
          required
        ></textarea>
      </div>

      <!-- Price, Capacity, Rules -->
      <div class="grid gap-4 md:grid-cols-3">
        <div class="space-y-2">
          <label for="price" class="block text-sm font-semibold text-[#222222]">
            Prix par nuit (€)
          </label>
          <input
            id="price"
            v-model.number="form.price_per_night"
            type="number"
            min="1"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
            required
          />
        </div>
        <div class="space-y-2">
          <label for="capacity" class="block text-sm font-semibold text-[#222222]">
            Capacité (personnes)
          </label>
          <input
            id="capacity"
            v-model.number="form.guest_capacity"
            type="number"
            min="1"
            max="100"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
            required
          />
        </div>
        <div class="space-y-2">
          <label for="rules" class="block text-sm font-semibold text-[#222222]">
            Règles
            <span class="font-normal text-gray-500">(optionnel)</span>
          </label>
          <input
            id="rules"
            v-model="form.rules"
            type="text"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
          />
        </div>
      </div>

      <div class="rounded-2xl border border-gray-200 bg-white p-4">
        <div class="flex items-start justify-between gap-4">
          <div>
            <p class="text-sm font-semibold text-[#222222]">Cashoula direct</p>
            <p class="mt-1 text-xs text-gray-500">
              Active une roue 50/50 au checkout qui peut rendre la réservation gratuite.
            </p>
          </div>
          <label class="relative inline-flex cursor-pointer items-center">
            <input
              v-model="form.cashoula_direct_enabled"
              type="checkbox"
              class="peer sr-only"
            />
            <div
              class="h-6 w-11 rounded-full bg-gray-200 transition peer-checked:bg-[#FF385C]"
            ></div>
            <span
              class="absolute left-1 top-1 h-4 w-4 rounded-full bg-white transition peer-checked:translate-x-5"
            ></span>
          </label>
        </div>
      </div>

      <!-- Amenities -->
      <div class="space-y-3">
        <label class="block text-sm font-semibold text-[#222222]">Équipements</label>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <label
            v-for="amenity in AMENITY_OPTIONS"
            :key="amenity.id"
            class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm text-[#222222] transition hover:border-gray-300 has-[:checked]:border-black has-[:checked]:bg-gray-50"
          >
            <input
              v-model="form.amenities"
              type="checkbox"
              :value="amenity.id"
              class="h-4 w-4 rounded border-gray-300 text-[#222222] focus:ring-black"
            />
            <span>{{ amenity.label }}</span>
          </label>
        </div>
      </div>

      <!-- Existing Images -->
      <div class="space-y-3">
        <div class="flex items-center justify-between">
          <label class="block text-sm font-semibold text-[#222222]">Images existantes</label>
          <span
            v-if="isReordering"
            class="inline-flex items-center gap-1.5 text-xs text-emerald-600"
          >
            <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
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
            Sauvegarde en cours...
          </span>
        </div>

        <div v-if="orderedExistingImages.length" class="grid gap-3 md:grid-cols-2">
          <div
            v-for="(image, index) in orderedExistingImages"
            :key="image.id"
            class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-3"
          >
            <img
              :src="image.url"
              :alt="`Image ${image.position}`"
              class="h-16 w-16 rounded-lg object-cover"
            />
            <div class="flex-1">
              <p class="text-sm font-medium text-[#222222]">Position {{ image.position }}</p>
            </div>
            <div class="flex items-center gap-1">
              <button
                class="rounded-lg border border-gray-200 p-2 text-gray-600 transition hover:border-black hover:text-[#222222] disabled:cursor-not-allowed disabled:opacity-40"
                type="button"
                :disabled="isReordering || index === 0"
                aria-label="Déplacer vers le haut"
                @click="moveExistingImage(image.id, 'up')"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M5 15l7-7 7 7"
                  />
                </svg>
              </button>
              <button
                class="rounded-lg border border-gray-200 p-2 text-gray-600 transition hover:border-black hover:text-[#222222] disabled:cursor-not-allowed disabled:opacity-40"
                type="button"
                :disabled="isReordering || index === orderedExistingImages.length - 1"
                aria-label="Déplacer vers le bas"
                @click="moveExistingImage(image.id, 'down')"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M19 9l-7 7-7-7"
                  />
                </svg>
              </button>
            </div>
          </div>
        </div>

        <div
          v-else
          class="rounded-xl border border-dashed border-gray-300 p-6 text-center text-sm text-gray-500"
        >
          Aucune image enregistrée pour cette annonce.
        </div>
      </div>

      <!-- Add New Images -->
      <div class="space-y-3">
        <label class="block text-sm font-semibold text-[#222222]">Ajouter des images</label>
        <div
          class="rounded-2xl border-2 border-dashed px-6 py-8 text-center transition"
          :class="
            isDragActive
              ? 'border-[#222222] bg-gray-50'
              : 'border-gray-300 bg-white hover:border-gray-400'
          "
          @drop="handleDrop"
          @dragover="handleDragOver"
          @dragleave="handleDragLeave"
        >
          <svg
            class="mx-auto h-10 w-10 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
          <p class="mt-3 text-sm font-semibold text-[#222222]">
            Glissez-déposez vos images ici
          </p>
          <p class="mt-1 text-xs text-gray-500">PNG, JPG, WebP — plusieurs fichiers autorisés</p>
          <label
            class="mt-4 inline-flex cursor-pointer items-center gap-2 rounded-lg border border-gray-300 px-4 py-2 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
          >
            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
              <path
                stroke="currentColor"
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
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

        <!-- New Images Preview -->
        <div v-if="newImages.length" class="space-y-3">
          <p class="text-xs text-gray-500">
            {{ newImages.length }} nouvelle(s) image(s) en attente d'upload.
          </p>
          <div class="grid gap-3 md:grid-cols-2">
            <div
              v-for="image in newImages"
              :key="image.id"
              class="flex items-center gap-4 rounded-xl border border-amber-200 bg-amber-50 p-3"
            >
              <img
                :src="image.preview"
                :alt="image.file.name"
                class="h-16 w-16 rounded-lg object-cover"
              />
              <div class="flex-1">
                <p class="text-sm font-medium text-[#222222]">Nouvelle image</p>
                <p class="text-xs text-gray-500 truncate">{{ image.file.name }}</p>
              </div>
              <button
                class="rounded-lg border border-red-200 p-2 text-red-600 transition hover:border-red-300 hover:bg-red-50"
                type="button"
                aria-label="Supprimer l'image"
                @click="removeNewImage(image.id)"
              >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                  <path
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M6 18L18 6M6 6l12 12"
                  />
                </svg>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Success Message -->
      <AlertMessage v-if="success" :message="success" type="success" />

      <!-- Submit Button -->
      <button
        class="w-full rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="isSubmitting"
        type="submit"
      >
        {{ isSubmitting ? 'Mise à jour en cours...' : 'Mettre à jour l\'annonce' }}
      </button>
    </form>
  </section>
</template>
