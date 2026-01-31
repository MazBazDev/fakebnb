<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { deleteListing, fetchMyListings, type Listing } from '@/services/listings'

const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const deleteBusy = ref<number[]>([])
const deleteError = ref<string | null>(null)
const search = ref('')
const page = ref(1)
const perPage = ref(9)
const lastPage = ref(1)

const filteredListings = computed(() => listings.value)

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const response = await fetchMyListings({
      search: search.value || undefined,
      page: page.value,
      per_page: perPage.value,
    })
    listings.value = response.data
    lastPage.value = response.meta?.last_page ?? 1
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger vos annonces.'
  } finally {
    isLoading.value = false
  }
}

async function removeListing(listing: Listing) {
  if (deleteBusy.value.includes(listing.id)) return
  deleteBusy.value = [...deleteBusy.value, listing.id]
  deleteError.value = null

  try {
    await deleteListing(listing.id)
    listings.value = listings.value.filter((item) => item.id !== listing.id)
    if (listings.value.length === 0 && page.value > 1) {
      page.value -= 1
      await load()
    }
  } catch (err) {
    deleteError.value = err instanceof Error ? err.message : 'Impossible de supprimer l’annonce.'
  } finally {
    deleteBusy.value = deleteBusy.value.filter((id) => id !== listing.id)
  }
}

onMounted(load)

watch(search, () => {
  page.value = 1
  load()
})

watch(page, () => {
  load()
})
</script>

<template>
  <section class="space-y-8">
    <header class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
      <div>
        <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Mes annonces</p>
        <h1 class="text-3xl font-semibold text-slate-900">Vos logements publiés</h1>
      </div>
      <RouterLink
        to="/host/listings/new"
        class="inline-flex rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
      >
        Nouvelle annonce
      </RouterLink>
    </header>

    <div v-if="error" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ error }}
    </div>
    <div v-if="deleteError" class="rounded-xl bg-rose-50 px-3 py-2 text-sm text-rose-600">
      {{ deleteError }}
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-4">
      <label class="text-xs font-semibold text-slate-600">Rechercher une annonce</label>
      <input
        v-model="search"
        type="search"
        placeholder="Titre, ville, description..."
        class="mt-2 w-full rounded-xl border border-slate-200 px-4 py-2 text-sm text-slate-700 outline-none transition focus:border-slate-400"
      />
    </div>

    <div
      v-if="isLoading"
      class="rounded-2xl border border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Chargement des annonces...
    </div>

    <div
      v-else-if="filteredListings.length === 0"
      class="rounded-2xl border border-dashed border-slate-200 bg-white p-6 text-sm text-slate-500"
    >
      Aucune annonce ne correspond à votre recherche.
    </div>

    <div v-else class="grid gap-5 md:grid-cols-2 lg:grid-cols-3">
      <article
        v-for="listing in filteredListings"
        :key="listing.id"
        class="group flex h-full flex-col rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"
      >
        <div class="flex-1 space-y-3">
          <div class="flex items-center justify-between">
            <span class="text-xs uppercase tracking-[0.2em] text-slate-400">
              {{ listing.city }}
            </span>
            <span class="text-xs font-semibold text-slate-700">
              {{ listing.price_per_night }} €/nuit
            </span>
          </div>
          <h2 class="text-lg font-semibold text-slate-900">{{ listing.title }}</h2>
          <p class="text-sm text-slate-500">
            {{ listing.description.slice(0, 120) }}{{ listing.description.length > 120 ? '…' : '' }}
          </p>
        </div>

        <div v-if="listing.images?.length" class="mt-3 overflow-hidden rounded-2xl">
          <img
            :src="listing.images[0].url"
            class="h-40 w-full object-cover transition duration-300 group-hover:scale-105"
            alt=""
          />
        </div>

        <div class="mt-4 flex items-center justify-between text-sm font-semibold">
          <RouterLink
            :to="`/listings/${listing.id}`"
            class="text-slate-900 group-hover:underline"
          >
            Voir le détail
          </RouterLink>
          <RouterLink
            :to="`/host/listings/${listing.id}/messages`"
            class="text-slate-600 hover:text-slate-900"
          >
            Messagerie
          </RouterLink>
          <RouterLink
            :to="`/host/listings/${listing.id}/edit`"
            class="text-slate-600 hover:text-slate-900"
          >
            Modifier
          </RouterLink>
          <button
            class="text-rose-600 hover:text-rose-700 disabled:opacity-60"
            type="button"
            :disabled="deleteBusy.includes(listing.id)"
            @click="removeListing(listing)"
          >
            {{ deleteBusy.includes(listing.id) ? 'Suppression...' : 'Supprimer' }}
          </button>
        </div>
      </article>
    </div>

    <div
      v-if="lastPage > 1"
      class="flex flex-wrap items-center justify-center gap-3 text-xs font-semibold text-slate-600"
    >
      <button
        class="rounded-full border border-slate-200 px-4 py-2 disabled:opacity-50"
        type="button"
        :disabled="page <= 1"
        @click="page -= 1"
      >
        Précédent
      </button>
      <span>Page {{ page }} / {{ lastPage }}</span>
      <button
        class="rounded-full border border-slate-200 px-4 py-2 disabled:opacity-50"
        type="button"
        :disabled="page >= lastPage"
        @click="page += 1"
      >
        Suivant
      </button>
    </div>
  </section>
</template>
