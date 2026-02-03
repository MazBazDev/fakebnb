<script setup lang="ts">
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import { startOAuthFlow } from '@/services/oauth'
import { useFormSubmit } from '@/composables'
import { PageHeader, AlertMessage } from '@/components/ui'

const auth = useAuthStore()

// Form state
const name = ref('')
const email = ref('')
const password = ref('')

// Form submission
const { isSubmitting, error, submit } = useFormSubmit(
  async () => {
    await auth.register({
      name: name.value,
      email: email.value,
      password: password.value,
    })
    await startOAuthFlow('/listings')
  },
  { errorMessage: 'Inscription impossible.' }
)
</script>

<template>
  <section class="mx-auto max-w-md space-y-8">
    <PageHeader
      title="Bienvenue sur Fakebnb"
      subtitle="Créez votre compte pour commencer"
      :breadcrumbs="[{ label: 'Accueil', to: '/' }, { label: 'Inscription' }]"
    />

    <div class="rounded-2xl border border-gray-200 bg-white p-8 shadow-sm">
      <form class="space-y-6" @submit.prevent="submit">
        <!-- Name -->
        <div class="space-y-2">
          <label for="name" class="block text-sm font-semibold text-[#222222]">
            Nom complet
          </label>
          <input
            id="name"
            v-model="name"
            type="text"
            autocomplete="name"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
            placeholder="Jean Dupont"
            required
          />
        </div>

        <!-- Email -->
        <div class="space-y-2">
          <label for="email" class="block text-sm font-semibold text-[#222222]">
            Adresse e-mail
          </label>
          <input
            id="email"
            v-model="email"
            type="email"
            autocomplete="email"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
            placeholder="nom@exemple.com"
            required
          />
        </div>

        <!-- Password -->
        <div class="space-y-2">
          <label for="password" class="block text-sm font-semibold text-[#222222]">
            Mot de passe
          </label>
          <input
            id="password"
            v-model="password"
            type="password"
            autocomplete="new-password"
            class="w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
            placeholder="••••••••"
            required
          />
          <p class="text-xs text-gray-500">Minimum 8 caractères recommandés</p>
        </div>

        <!-- Error Message -->
        <AlertMessage v-if="error" :message="error" type="error" />

        <!-- Submit Button -->
        <button
          class="w-full rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-base font-semibold text-white shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="isSubmitting"
          type="submit"
        >
          <span v-if="isSubmitting" class="inline-flex items-center gap-2">
            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
              <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
              ></circle>
              <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
              ></path>
            </svg>
            Création du compte...
          </span>
          <span v-else>Accepter et continuer</span>
        </button>

        <!-- Legal Text -->
        <p class="text-xs leading-relaxed text-gray-500">
          En sélectionnant <strong class="font-semibold">Accepter et continuer</strong>,
          j'accepte les Conditions d'utilisation de Fakebnb et je reconnais avoir pris
          connaissance de la Politique de confidentialité.
        </p>
      </form>

      <!-- Footer -->
      <div class="mt-6 border-t border-gray-100 pt-6 text-center">
        <p class="text-sm text-gray-600">
          Vous avez déjà un compte ?
          <RouterLink
            to="/login"
            class="font-semibold text-[#222222] underline transition hover:text-[#E61E4D]"
          >
            Connectez-vous
          </RouterLink>
        </p>
      </div>
    </div>
  </section>
</template>
