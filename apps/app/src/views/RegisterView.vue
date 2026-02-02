<script setup lang="ts">
import { ref } from 'vue'
import Breadcrumbs from "@/components/Breadcrumbs.vue"
import { startOAuthFlow } from '@/services/oauth'

const auth = useAuthStore()

const name = ref('')
const email = ref('')
const password = ref('')
const error = ref<string | null>(null)
const isLoading = ref(false)

async function submit() {
  error.value = null
  isLoading.value = true

  try {
    await auth.register({ name: name.value, email: email.value, password: password.value })
    await startOAuthFlow('/listings')
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Inscription impossible.'
  } finally {
    isLoading.value = false
  }
}
</script>

<template>
  <section class="auth-container">
    <header class="auth-header">
      <h1 class="auth-title">Bienvenue sur Fakebnb</h1>
      <p class="auth-subtitle">Créez votre compte pour commencer</p>
    </header>

    <div class="auth-card">
      <form class="auth-form" @submit.prevent="submit">
        <div class="form-group">
          <label class="form-label">Nom complet</label>
          <input
            v-model="name"
            type="text"
            autocomplete="name"
            class="form-input"
            placeholder="Jean Dupont"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Adresse e-mail</label>
          <input
            v-model="email"
            type="email"
            autocomplete="email"
            class="form-input"
            placeholder="nom@exemple.com"
            required
          />
        </div>

        <div class="form-group">
          <label class="form-label">Mot de passe</label>
          <input
            v-model="password"
            type="password"
            autocomplete="new-password"
            class="form-input"
            placeholder="••••••••"
            required
          />
          <p class="form-hint">Minimum 8 caractères recommandés</p>
        </div>

        <p v-if="error" class="error-message">
          {{ error }}
        </p>

        <button
          class="submit-btn"
          :disabled="isLoading"
          type="submit"
        >
          {{ isLoading ? 'Création...' : 'Accepter et continuer' }}
        </button>

        <p class="legal-text">
          En sélectionnant <strong>Accepter et continuer</strong>, j'accepte les Conditions d'utilisation de Fakebnb et je reconnais avoir pris connaissance de la Politique de confidentialité.
        </p>
      </form>

      <div class="auth-footer">
        <p class="footer-text">
          Vous avez déjà un compte ?
          <RouterLink to="/login" class="footer-link">
            Connectez-vous
          </RouterLink>
        </p>
      </div>
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
  text-align: center;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.auth-title {
  font-size: 2.25rem;
  font-weight: 600;
  letter-spacing: -0.02em;
  color: var(--color-text-primary);
}

.auth-subtitle {
  font-size: 0.9375rem;
  color: var(--color-text-secondary);
}

.auth-card {
  padding: 2rem;
  border-radius: 1rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  box-shadow: var(--shadow-sm);
}

.auth-form {
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  font-size: 1rem;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.form-input::placeholder {
  color: var(--color-text-tertiary);
}

.form-input:focus {
  outline: none;
  border-color: var(--color-text-primary);
  box-shadow: 0 0 0 2px var(--color-text-primary);
}

.form-hint {
  font-size: 0.75rem;
  color: var(--color-text-tertiary);
}

.error-message {
  padding: 0.75rem 1rem;
  border-radius: 0.5rem;
  background-color: var(--color-error-bg);
  color: var(--color-error);
  font-size: 0.875rem;
}

.submit-btn {
  width: 100%;
  padding: 0.875rem 1.5rem;
  border-radius: 0.5rem;
  border: none;
  background: linear-gradient(to right, #E61E4D, #D70466);
  color: white;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition-fast), opacity var(--transition-fast);
}

.submit-btn:hover:not(:disabled) {
  box-shadow: var(--shadow-md);
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.legal-text {
  font-size: 0.75rem;
  line-height: 1.5;
  color: var(--color-text-tertiary);
}

.legal-text strong {
  font-weight: 600;
}

.auth-footer {
  margin-top: 1.5rem;
  padding-top: 1.5rem;
  border-top: 1px solid var(--color-border-primary);
  text-align: center;
}

.footer-text {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.footer-link {
  font-weight: 600;
  color: var(--color-text-primary);
  text-decoration: underline;
  transition: color var(--transition-fast);
}

.footer-link:hover {
  color: var(--color-text-secondary);
}
</style>
