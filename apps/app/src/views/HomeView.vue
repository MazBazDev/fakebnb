<script setup lang="ts"></script>

<template>
  <section class="space-y-6">
    <header class="space-y-2">
      <p class="text-sm uppercase tracking-[0.2em] text-slate-500">MiniBnB</p>
      <h1 class="text-3xl font-semibold text-slate-900">Réservez, publiez, échangez.</h1>
      <p class="max-w-2xl text-slate-600">
        Base SPA Vue + API Laravel. Cette page sert de point d’entrée en attendant les
        fonctionnalités listings et bookings.
      </p>
    </header>

    <div class="grid gap-4 md:grid-cols-3">
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-sm font-semibold text-slate-700">Annonces</p>
        <p class="text-sm text-slate-500">Liste publique et détail d’annonce (Lot 3).</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-sm font-semibold text-slate-700">Réservations</p>
        <p class="text-sm text-slate-500">Calendrier + anti-chevauchement (Lot 4).</p>
      </div>
      <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <p class="text-sm font-semibold text-slate-700">Messagerie</p>
        <p class="text-sm text-slate-500">Conversations entre client et hôte (Lot 5).</p>
      </div>
    </div>
  </section>
</template>
