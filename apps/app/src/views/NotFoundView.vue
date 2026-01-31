<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue'
</script>

<template>
  <section class="mx-auto max-w-md space-y-3 rounded-3xl border border-slate-200 bg-white p-6 text-center">
    <Breadcrumbs :items="[{ label: 'Accueil', to: '/' }, { label: 'Introuvable' }]" />
    <h1 class="text-2xl font-semibold text-slate-900">Page introuvable</h1>
    <p class="text-sm text-slate-500">La page demandée n’existe pas encore.</p>
  </section>
</template>
