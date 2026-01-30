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
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">Espace privé</p>
      <h1 class="text-3xl font-semibold text-slate-900">Bonjour {{ displayName }}</h1>
      <p class="text-sm text-slate-500">
        Ce tableau de bord sera enrichi avec les annonces, réservations et la messagerie.
      </p>
    </header>

    <div class="grid gap-4 md:grid-cols-2">
      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <p class="text-sm font-semibold text-slate-700">Rôle</p>
        <p class="text-sm text-slate-500">Client (par défaut)</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-4">
        <p class="text-sm font-semibold text-slate-700">Actions rapides</p>
        <p class="text-sm text-slate-500">
          Devenir hôte, créer une annonce, gérer les co-hôtes.
        </p>
      </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-5">
      <h2 class="text-sm font-semibold text-slate-700">Gestion des co-hôtes</h2>
      <p class="mt-2 text-sm text-slate-500">
        Accède à la liste et aux permissions des co-hôtes.
      </p>
      <RouterLink
        to="/dashboard/cohosts"
        class="mt-4 inline-flex rounded-full bg-slate-900 px-4 py-2 text-xs font-semibold text-white"
      >
        Ouvrir
      </RouterLink>
    </div>
  </section>
</template>
