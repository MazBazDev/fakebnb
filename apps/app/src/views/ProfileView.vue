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
  const file = input.files[0]
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

    auth.setSession(auth.token, updated)
    success.value = 'Profil mis à jour.'
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de mettre à jour le profil.'
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <section class="space-y-8">
    <header class="space-y-2">
      <Breadcrumbs :items="[{ label: 'Accueil', to: '/' }, { label: 'Profil' }]" />
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Profil</p>
      <h1 class="text-3xl font-semibold text-slate-900">Mon profil</h1>
      <p class="text-sm text-slate-500">
        Mets à jour tes informations de contact et ta photo.
      </p>
    </header>

    <div class="grid gap-6 lg:grid-cols-[280px_1fr]">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="flex flex-col items-center gap-4">
          <div
            class="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-slate-100 text-2xl font-semibold text-slate-700"
          >
            <img v-if="photoPreview" :src="photoPreview" class="h-full w-full object-cover" />
            <span v-else>{{ initials }}</span>
          </div>
          <label class="cursor-pointer text-xs font-semibold text-slate-600">
            Changer la photo
            <input type="file" accept="image/*" class="hidden" @change="handlePhotoChange" />
          </label>
        </div>
      </div>

      <form
        class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
        @submit.prevent="submit"
      >
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Nom</label>
          <input
            v-model="form.name"
            type="text"
            class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
            required
          />
        </div>
        <div class="space-y-2">
          <label class="text-sm font-medium text-slate-700">Adresse</label>
          <input
            v-model="form.address"
            type="text"
            class="w-full rounded-xl border border-slate-200 px-4 py-2 text-sm"
          />
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
          {{ isSubmitting ? 'Mise à jour...' : 'Enregistrer' }}
        </button>
      </form>
    </div>
  </section>
</template>
