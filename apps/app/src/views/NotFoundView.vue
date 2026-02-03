<script setup lang="ts">
import { PageHeader, EmptyState } from '@/components/ui'
</script>

<template>
  <section class="mx-auto max-w-2xl space-y-8">
    <PageHeader
      title="Page introuvable"
      subtitle="La page que vous recherchez n'existe pas ou a été déplacée"
      :breadcrumbs="[{ label: 'Accueil', to: '/' }, { label: 'Introuvable' }]"
    />

    <EmptyState
      title="Erreur 404"
      subtitle="Vérifiez l'URL ou retournez à l'accueil pour continuer votre navigation"
      icon="notFound"
      action-text="Retour à l'accueil"
      action-to="/"
    />
  </section>
</template>
