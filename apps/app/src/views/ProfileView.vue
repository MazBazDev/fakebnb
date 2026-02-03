<script setup lang="ts">
import { computed, ref } from 'vue'
import { useAuthStore } from '@/stores/auth'
import { updateProfile } from '@/services/profile'
import { useFormSubmit } from '@/composables'
import { PageHeader, AlertMessage } from '@/components/ui'

const auth = useAuthStore()

// Photo handling
const photoPreview = ref<string | null>(auth.user?.profile_photo_url ?? null)
const photoFile = ref<File | null>(null)

// Form state
const form = ref({
  name: auth.user?.name ?? '',
  address: auth.user?.address ?? '',
})

// Form submission
const { isSubmitting, error, success, submit } = useFormSubmit(
  async () => {
    const updated = await updateProfile({
      name: form.value.name,
      address: form.value.address || null,
      photo: photoFile.value,
    })

    auth.setSession(auth.token, auth.refreshToken, auth.expiresAt, updated)
    return updated
  },
  {
    successMessage: 'Profil mis à jour avec succès.',
    errorMessage: 'Impossible de mettre à jour le profil.',
  }
)

// Computed
const initials = computed(() => {
  if (!form.value.name) return 'MB'
  return form.value.name
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0]?.toUpperCase())
    .slice(0, 2)
    .join('')
})

const breadcrumbs = [
  { label: 'Accueil', to: '/' },
  { label: 'Profil' },
]

// Methods
function handlePhotoChange(event: Event) {
  const input = event.target as HTMLInputElement
  if (!input.files?.length) return
  const file = input.files.item(0)
  if (!file) return

  // Revoke previous preview URL to prevent memory leaks
  if (photoPreview.value && photoPreview.value !== auth.user?.profile_photo_url) {
    URL.revokeObjectURL(photoPreview.value)
  }

  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

function resetForm() {
  form.value.name = auth.user?.name ?? ''
  form.value.address = auth.user?.address ?? ''

  // Revoke preview URL if it's not the original
  if (photoPreview.value && photoPreview.value !== auth.user?.profile_photo_url) {
    URL.revokeObjectURL(photoPreview.value)
  }

  photoPreview.value = auth.user?.profile_photo_url ?? null
  photoFile.value = null
}
</script>

<template>
  <section class="mx-auto max-w-4xl space-y-8">
    <PageHeader
      title="Informations personnelles"
      subtitle="Mettez à jour vos informations et découvrez comment elles sont utilisées"
      :breadcrumbs="breadcrumbs"
    />

    <div class="space-y-8">
      <!-- Photo Section -->
      <div class="rounded-2xl border border-gray-200 bg-white p-8">
        <div class="flex flex-col items-start gap-6 md:flex-row md:items-center md:justify-between">
          <div class="flex items-center gap-6">
            <div
              class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-700 text-3xl font-semibold text-white shadow-sm"
            >
              <img
                v-if="photoPreview"
                :src="photoPreview"
                alt="Photo de profil"
                class="h-full w-full object-cover"
              />
              <span v-else>{{ initials }}</span>
            </div>
            <div>
              <h2 class="text-lg font-semibold text-[#222222]">Photo de profil</h2>
              <p class="mt-1 text-sm text-gray-600">
                Ajoutez une photo pour personnaliser votre compte
              </p>
            </div>
          </div>
          <label
            class="cursor-pointer rounded-lg border border-gray-300 px-5 py-2.5 text-sm font-semibold text-[#222222] transition hover:border-black hover:bg-gray-50"
          >
            <span class="inline-flex items-center gap-2">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"
                />
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"
                />
              </svg>
              Modifier
            </span>
            <input
              type="file"
              accept="image/*"
              class="hidden"
              @change="handlePhotoChange"
            />
          </label>
        </div>
      </div>

      <!-- Form Section -->
      <div class="rounded-2xl border border-gray-200 bg-white p-8">
        <form class="space-y-8" @submit.prevent="submit">
          <div class="space-y-6">
            <div class="grid gap-6 md:grid-cols-2">
              <!-- Name -->
              <div class="space-y-2">
                <label for="name" class="block text-sm font-semibold text-[#222222]">
                  Nom complet
                  <span class="text-red-500">*</span>
                </label>
                <input
                  id="name"
                  v-model="form.name"
                  type="text"
                  class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
                  placeholder="Jean Dupont"
                  required
                />
                <p class="text-xs text-gray-500">
                  Votre nom apparaîtra sur votre profil et lors de vos réservations
                </p>
              </div>

              <!-- Email (readonly) -->
              <div class="space-y-2">
                <label for="email" class="block text-sm font-semibold text-[#222222]">
                  Adresse e-mail
                </label>
                <input
                  id="email"
                  type="email"
                  :value="auth.user?.email"
                  class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-base text-gray-500"
                  disabled
                  readonly
                />
                <p class="text-xs text-gray-500">L'adresse e-mail ne peut pas être modifiée</p>
              </div>
            </div>

            <!-- Address -->
            <div class="space-y-2">
              <label for="address" class="block text-sm font-semibold text-[#222222]">
                Adresse
                <span class="font-normal text-gray-500">(optionnel)</span>
              </label>
              <input
                id="address"
                v-model="form.address"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
                placeholder="123 Rue de la Paix, 75001 Paris"
              />
              <p class="text-xs text-gray-500">Votre adresse ne sera pas affichée publiquement</p>
            </div>
          </div>

          <!-- Messages -->
          <AlertMessage v-if="error" :message="error" type="error" />
          <AlertMessage v-if="success" :message="success" type="success" />

          <!-- Actions -->
          <div class="flex items-center justify-between border-t border-gray-200 pt-6">
            <button
              type="button"
              class="text-sm font-semibold text-gray-600 underline transition hover:text-[#222222]"
              @click="resetForm"
            >
              Annuler
            </button>
            <button
              class="rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
              :disabled="isSubmitting"
              type="submit"
            >
              {{ isSubmitting ? 'Enregistrement...' : 'Enregistrer' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </section>
</template>
