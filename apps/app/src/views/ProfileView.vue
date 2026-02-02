<script setup lang="ts">
import { computed, ref } from 'vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { useAuthStore } from '@/stores/auth'
import { updateProfile } from '@/services/profile'

const auth = useAuthStore()
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const success = ref<string | null>(null)
const photoPreview = ref<string | null>(auth.user?.profile_photo_url ?? null)
const photoFile = ref<File | null>(null)

const form = ref({
  name: auth.user?.name ?? '',
  address: auth.user?.address ?? '',
})

const initials = computed(() => {
  if (!form.value.name) return 'MB'
  return form.value.name
    .split(' ')
    .filter(Boolean)
    .map((part) => part[0]?.toUpperCase())
    .slice(0, 2)
    .join('')
})

function handlePhotoChange(event: Event) {
  const input = event.target as HTMLInputElement
  if (!input.files?.length) return
  const file = input.files.item(0)
  if (!file) return
  photoFile.value = file
  photoPreview.value = URL.createObjectURL(file)
}

async function submit() {
  error.value = null
  success.value = null
  isSubmitting.value = true

  try {
    const updated = await updateProfile({
      name: form.value.name,
      address: form.value.address || null,
      photo: photoFile.value,
    })

    auth.setSession(auth.token, auth.refreshToken, auth.expiresAt, updated)
    success.value = "Profil mis à jour."
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de mettre à jour le profil.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="mx-auto max-w-4xl space-y-8">
    <header class="space-y-3">
      <Breadcrumbs :items="[{ label: 'Accueil', to: '/' }, { label: 'Profil' }]" />
      <h1 class="text-4xl font-semibold tracking-tight text-[#222222]">Informations personnelles</h1>
      <p class="text-[15px] text-gray-600">
        Mettez à jour vos informations et découvrez comment elles sont utilisées
      </p>
    </header>

    <div class="space-y-8">
      <!-- Photo Section -->
      <div class="rounded-2xl border border-gray-200 bg-white p-8">
        <div class="flex flex-col items-start gap-6 md:flex-row md:items-center md:justify-between">
          <div class="flex items-center gap-6">
            <div
              class="flex h-24 w-24 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gray-700 text-3xl font-semibold text-white shadow-sm"
            >
              <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover" />
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
            Modifier
            <input type="file" accept="image/*" class="hidden" @change="handlePhotoChange" />
          </label>
        </div>
      </div>

      <!-- Form Section -->
      <div class="rounded-2xl border border-gray-200 bg-white p-8">
        <form class="space-y-8" @submit.prevent="submit">
          <div class="space-y-6">
            <div class="grid gap-6 md:grid-cols-2">
              <div class="space-y-2">
                <label class="text-sm font-semibold text-[#222222]">
                  Nom complet
                  <span class="text-red-500">*</span>
                </label>
                <input
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

              <div class="space-y-2">
                <label class="text-sm font-semibold text-[#222222]">Adresse e-mail</label>
                <input
                  type="email"
                  :value="auth.user?.email"
                  class="w-full rounded-lg border border-gray-300 bg-gray-50 px-4 py-3 text-base text-gray-500"
                  disabled
                  readonly
                />
                <p class="text-xs text-gray-500">L'adresse e-mail ne peut pas être modifiée</p>
              </div>
            </div>

            <div class="space-y-2">
              <label class="text-sm font-semibold text-[#222222]">Adresse</label>
              <input
                v-model="form.address"
                type="text"
                class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
                placeholder="123 Rue de la Paix, 75001 Paris"
              />
              <p class="text-xs text-gray-500">Votre adresse ne sera pas affichée publiquement</p>
            </div>
          </div>

          <div v-if="error" class="rounded-lg bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ error }}
          </div>

          <div v-if="success" class="rounded-lg bg-green-50 px-4 py-3 text-sm text-green-700">
            {{ success }}
          </div>

          <div class="flex items-center justify-between border-t border-gray-200 pt-6">
            <button
              type="button"
              class="text-sm font-semibold text-gray-600 underline transition hover:text-[#222222]"
              @click="
                () => {
                  form.name = auth.user?.name ?? ''
                  form.address = auth.user?.address ?? ''
                  photoPreview = auth.user?.profile_photo_url ?? null
                  photoFile = null
                  error = null
                  success = null
                }
              "
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
