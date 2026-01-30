<script setup lang="ts">
import { computed } from 'vue'
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
        <p class="text-sm text-slate-500">Devenir hôte, créer une annonce, etc.</p>
      </div>
    </div>
  </section>
</template>
