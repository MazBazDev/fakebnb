<script setup lang="ts">
import { ref } from 'vue'
import { useRoute } from 'vue-router'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import { startOAuthFlow } from '@/services/oauth'

const route = useRoute()
const error = ref<string | null>(null)
const isLoading = ref(false)

async function submit() {
  error.value = null
  isLoading.value = true

  try {
    const redirect = (route.query.redirect as string | undefined) ?? '/listings'
    await startOAuthFlow(redirect)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Connexion impossible.'
    isLoading.value = false
  }
}
</script>

<template>
  <section class="auth-container">
    <header class="auth-header">
      <Breadcrumbs :items="[{ label: 'Accueil', to: '/' }, { label: 'Connexion' }]" />
      <h1 class="auth-title">Connexion</h1>
      <p class="auth-subtitle">Vous allez être redirigé vers Fakebnb SSO.</p>
    </header>

    <div class="auth-card">
      <p v-if="error" class="error-message">
        {{ error }}
      </p>

      <button
        class="submit-btn"
        :disabled="isLoading"
        type="button"
        @click="submit"
      >
        {{ isLoading ? 'Redirection...' : 'Continuer avec Fakebnb' }}
      </button>
    </div>
  </section>
</template>

<style scoped>
.auth-container {
  max-width: 28rem;
  margin: 0 auto;
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.auth-header {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.auth-title {
  font-size: 2rem;
  font-weight: 600;
  letter-spacing: -0.02em;
  color: var(--color-text-primary);
}

.auth-subtitle {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.auth-card {
  padding: 2rem;
  border-radius: 1.5rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  box-shadow: var(--shadow-sm);
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.error-message {
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  background-color: var(--color-error-bg);
  color: var(--color-error);
  font-size: 0.875rem;
}

.submit-btn {
  width: 100%;
  padding: 0.875rem 1.5rem;
  border-radius: 0.75rem;
  border: none;
  background: linear-gradient(to right, #E61E4D, #D70466);
  color: white;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition-fast), opacity var(--transition-fast), transform var(--transition-fast);
}

.submit-btn:hover:not(:disabled) {
  box-shadow: var(--shadow-md);
  transform: translateY(-1px);
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}
</style>
