<script setup lang="ts">
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { createListing, uploadListingImages } from '@/services/listings'
import { useFormSubmit } from '@/composables'
import { AMENITY_OPTIONS } from '@/constants/amenities'
import { PageHeader, AlertMessage } from '@/components/ui'

const router = useRouter()

// Form state
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

// Image handling
const images = ref<{ id: number; file: File; preview: string }[]>([])
let nextImageId = 0
const isDragActive = ref(false)

const orderedImages = computed(() => images.value)

// Form submission
const { isSubmitting, error, success, submit } = useFormSubmit(
  async () => {
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

    await router.push(`/host/listings/${listing.id}/edit`)
    return listing
  },
  {
    successMessage: 'Annonce publiée avec succès.',
    errorMessage: "Impossible de créer l'annonce.",
  }
)

// Image methods
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
  if (!item) return
  images.value.splice(nextIndex, 0, item)
}

function removeImage(id: number) {
  const image = images.value.find((item) => item.id === id)
  if (image) {
    URL.revokeObjectURL(image.preview)
  }
  images.value = images.value.filter((item) => item.id !== id)
}

function getImagePosition(id: number): number {
  return images.value.findIndex((item) => item.id === id) + 1
}
</script>

<template>
  <section class="space-y-8">
    <PageHeader
      title="Publier un logement"
      subtitle="Créez une annonce pour commencer à recevoir des réservations"
      :breadcrumbs="[
        { label: 'Hôte', to: '/host' },
        { label: 'Annonces', to: '/host/listings' },
        { label: 'Nouvelle annonce' },
      ]"
    />

    <form
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
            placeholder="Appartement lumineux au centre-ville"
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
            placeholder="Paris"
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
          placeholder="10 Rue de Rivoli"
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
          placeholder="Décrivez votre logement, ses atouts et son environnement..."
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
            placeholder="75"
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
            placeholder="4"
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
            placeholder="Non-fumeur, pas d'animaux..."
          />
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
        <p class="text-xs text-gray-500">Sélection multiple possible.</p>
      </div>

      <!-- Images -->
      <div class="space-y-3">
        <label class="block text-sm font-semibold text-[#222222]">Images</label>
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

        <!-- Image Preview List -->
        <div v-if="orderedImages.length" class="space-y-3">
          <p class="text-xs text-gray-500">
            Utilisez les boutons pour réorganiser l'ordre des images.
          </p>
          <div class="grid gap-3 md:grid-cols-2">
            <div
              v-for="image in orderedImages"
              :key="image.id"
              class="flex items-center gap-4 rounded-xl border border-gray-200 bg-white p-3"
            >
              <img
                :src="image.preview"
                :alt="`Image ${getImagePosition(image.id)}`"
                class="h-16 w-16 rounded-lg object-cover"
              />
              <div class="flex-1">
                <p class="text-sm font-medium text-[#222222]">
                  Position {{ getImagePosition(image.id) }}
                </p>
                <p class="text-xs text-gray-500">{{ image.file.name }}</p>
              </div>
              <div class="flex items-center gap-1">
                <button
                  class="rounded-lg border border-gray-200 p-2 text-gray-600 transition hover:border-black hover:text-[#222222] disabled:opacity-40"
                  type="button"
                  :disabled="getImagePosition(image.id) === 1"
                  aria-label="Déplacer vers le haut"
                  @click="moveImage(image.id, 'up')"
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
                  class="rounded-lg border border-gray-200 p-2 text-gray-600 transition hover:border-black hover:text-[#222222] disabled:opacity-40"
                  type="button"
                  :disabled="getImagePosition(image.id) === orderedImages.length"
                  aria-label="Déplacer vers le bas"
                  @click="moveImage(image.id, 'down')"
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
                <button
                  class="rounded-lg border border-red-200 p-2 text-red-600 transition hover:border-red-300 hover:bg-red-50"
                  type="button"
                  aria-label="Supprimer l'image"
                  @click="removeImage(image.id)"
                >
                  <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <path
                      stroke="currentColor"
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Messages -->
      <AlertMessage v-if="error" :message="error" type="error" />
      <AlertMessage v-if="success" :message="success" type="success" />

      <!-- Submit Button -->
      <button
        class="w-full rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
        :disabled="isSubmitting"
        type="submit"
      >
        {{ isSubmitting ? 'Publication en cours...' : 'Publier l\'annonce' }}
      </button>
    </form>
  </section>
</template>
