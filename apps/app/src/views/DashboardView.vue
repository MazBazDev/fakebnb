<script setup lang="ts">
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const displayName = computed(() => auth.user?.name ?? auth.user?.email ?? 'Utilisateur')
</script>

<template>
  <section class="space-y-6">
    <header class="space-y-2">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Espace hôte</p>
      <h1 class="text-3xl font-semibold text-slate-900">Bienvenue {{ displayName }}</h1>
      <p class="text-sm text-slate-500">
        Centralise tes annonces, réservations et messages dans un espace dédié.
      </p>
    </header>

    <div class="grid gap-4 md:grid-cols-2">
      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <p class="text-sm font-semibold text-slate-700">Rôle</p>
        <p class="text-sm text-slate-500">
          Tu deviens hôte automatiquement dès ta première annonce.
        </p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <p class="text-sm font-semibold text-slate-700">Actions rapides</p>
        <p class="text-sm text-slate-500">Créer une annonce, gérer les co-hôtes.</p>
        <RouterLink
          to="/host/listings/new"
          class="mt-3 inline-flex rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white"
        >
          Publier une annonce
        </RouterLink>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5">
      <h2 class="text-sm font-semibold text-slate-700">Gestion des co-hôtes</h2>
      <p class="mt-2 text-sm text-slate-500">
        Accède à la liste et aux permissions des co-hôtes.
      </p>
      <RouterLink
        to="/host/cohosts"
        class="mt-4 inline-flex rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white"
      >
        Ouvrir
      </RouterLink>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5">
      <h2 class="text-sm font-semibold text-slate-700">Publier une annonce</h2>
      <p class="mt-2 text-sm text-slate-500">
        Crée une annonce et rends-la visible publiquement.
      </p>
      <RouterLink
        to="/host/listings/new"
        class="mt-4 inline-flex rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
      >
        Nouveau listing
      </RouterLink>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5">
      <h2 class="text-sm font-semibold text-slate-700">Mes annonces</h2>
      <p class="mt-2 text-sm text-slate-500">
        Consulte et gère les annonces que tu as publiées.
      </p>
      <RouterLink
        to="/host/listings"
        class="mt-4 inline-flex rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
      >
        Voir mes annonces
      </RouterLink>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5">
      <h2 class="text-sm font-semibold text-slate-700">Réservations</h2>
      <p class="mt-2 text-sm text-slate-500">
        Consulte et crée des réservations pour les annonces.
      </p>
      <RouterLink
        to="/host/bookings"
        class="mt-4 inline-flex rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold text-slate-700"
      >
        Voir les réservations
      </RouterLink>
    </div>
  </section>
</template>
