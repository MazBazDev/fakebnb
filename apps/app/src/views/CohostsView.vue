<script setup lang="ts">
import { onMounted, ref } from 'vue'
import Breadcrumbs from '@/components/Breadcrumbs.vue'
import {
  createCohost,
  deleteCohost,
  fetchCohosts,
  updateCohost,
  type Cohost,
} from '@/services/cohosts'
import { fetchMyListings, type Listing } from '@/services/listings'

const cohosts = ref<Cohost[]>([])
const listings = ref<Listing[]>([])
const isLoading = ref(false)
const error = ref<string | null>(null)
const creating = ref(false)
const form = ref({
  listing_id: '',
  cohost_email: '',
  can_read_conversations: false,
  can_reply_messages: false,
  can_edit_listings: false,
})

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const listingsResponse = await fetchMyListings({ per_page: 100 })
    listings.value = listingsResponse.data ?? []
    const totalListings = listingsResponse.meta?.total ?? listingsResponse.data.length

    if (totalListings === 0) {
      error.value = 'Accès réservé aux hôtes ayant des annonces.'
      return
    }

    const cohostsData = await fetchCohosts()
    cohosts.value = cohostsData
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les co-hôtes.'
  } finally {
    isLoading.value = false
  }
}

async function submit() {
  error.value = null
  creating.value = true

  try {
    const created = await createCohost({
      listing_id: Number(form.value.listing_id),
      cohost_email: form.value.cohost_email,
      can_read_conversations: form.value.can_read_conversations,
      can_reply_messages: form.value.can_reply_messages,
      can_edit_listings: form.value.can_edit_listings,
    })
    cohosts.value = [created, ...cohosts.value]
    form.value = {
      listing_id: '',
      cohost_email: '',
      can_read_conversations: false,
      can_reply_messages: false,
      can_edit_listings: false,
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de créer le co-hôte.'
  } finally {
    creating.value = false
  }
}

type PermissionKey = 'can_read_conversations' | 'can_reply_messages' | 'can_edit_listings'

async function togglePermission(cohost: Cohost, key: PermissionKey) {
  error.value = null
  const nextValue = !cohost[key]

  try {
    const updated = await updateCohost(cohost.id, { [key]: nextValue })
    cohosts.value = cohosts.value.map((item) => (item.id === updated.id ? updated : item))
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de mettre à jour.'
  }
}

async function removeCohost(cohost: Cohost) {
  error.value = null

  try {
    await deleteCohost(cohost.id)
    cohosts.value = cohosts.value.filter((item) => item.id !== cohost.id)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de supprimer.'
  }
}

onMounted(load)
</script>

<template>
  <section class="cohosts-view">
    <header class="header">
      <Breadcrumbs :items="[{ label: 'Hôte', to: '/host' }, { label: 'Co-hôtes' }]" />
      <h1 class="title">Gérer les délégations</h1>
      <p class="subtitle">
        Ajoute un co-hôte par email et configure ses permissions par annonce.
      </p>
    </header>

    <div class="content-grid">
      <form
        class="form-card"
        @submit.prevent="submit"
      >
        <div class="form-group">
          <label class="form-label">Annonce</label>
          <div class="select-wrapper">
            <div class="select-icon-left">
              <svg class="icon" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"
                />
              </svg>
            </div>
            <select
              v-model="form.listing_id"
              class="form-select"
              required
            >
              <option value="" disabled>Choisir une annonce</option>
              <option v-for="listing in listings" :key="listing.id" :value="listing.id">
                {{ listing.title }} — {{ listing.city }}
              </option>
            </select>
            <div class="select-icon-right">
              <svg class="icon" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M19 9l-7 7-7-7"
                />
              </svg>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email du co-hôte</label>
          <div class="input-wrapper">
            <div class="input-icon-left">
              <svg class="icon" fill="none" viewBox="0 0 24 24">
                <path
                  stroke="currentColor"
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="1.5"
                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"
                />
              </svg>
            </div>
            <input
              v-model="form.cohost_email"
              type="email"
              class="form-input"
              placeholder="exemple@email.com"
              required
            />
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Permissions</label>
          <div class="permissions-list">
            <label class="permission-item">
              <input
                v-model="form.can_read_conversations"
                type="checkbox"
                class="permission-checkbox"
              />
              <span class="permission-text">Lire les conversations</span>
            </label>
            <label class="permission-item">
              <input
                v-model="form.can_reply_messages"
                type="checkbox"
                class="permission-checkbox"
              />
              <span class="permission-text">Répondre aux messages</span>
            </label>
            <label class="permission-item">
              <input
                v-model="form.can_edit_listings"
                type="checkbox"
                class="permission-checkbox"
              />
              <span class="permission-text">Modifier les annonces</span>
            </label>
          </div>
        </div>

        <button
          class="submit-btn"
          :disabled="creating"
          type="submit"
        >
          {{ creating ? 'Création...' : 'Ajouter un co-hôte' }}
        </button>
      </form>

      <div class="cohosts-list">
        <div v-if="error" class="error-message">
          {{ error }}
        </div>

        <div
          v-if="isLoading"
          class="loading-card"
        >
          <div class="spinner"></div>
          <span>Chargement des co-hôtes...</span>
        </div>

        <div
          v-else-if="cohosts.length === 0"
          class="empty-card"
        >
          <svg class="empty-icon" fill="none" viewBox="0 0 24 24">
            <path
              stroke="currentColor"
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="1.5"
              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"
            />
          </svg>
          <p>Aucun co-hôte pour le moment.</p>
        </div>

        <div v-else class="cohosts-cards">
          <article
            v-for="cohost in cohosts"
            :key="cohost.id"
            class="cohost-card"
          >
            <div class="cohost-header">
              <div class="cohost-info">
                <p class="cohost-name">
                  {{ cohost.cohost?.name ?? 'Utilisateur #' + cohost.cohost_user_id }}
                </p>
                <p class="cohost-email">{{ cohost.cohost?.email }}</p>
                <p class="cohost-listing">
                  {{ cohost.listing?.title ?? 'Annonce #' + cohost.listing_id }}
                </p>
              </div>
              <button
                class="delete-btn"
                type="button"
                @click="removeCohost(cohost)"
              >
                Supprimer
              </button>
            </div>

            <div class="cohost-permissions">
              <label class="permission-toggle">
                <input
                  type="checkbox"
                  class="permission-checkbox"
                  :checked="cohost.can_read_conversations"
                  @change="togglePermission(cohost, 'can_read_conversations')"
                />
                <span class="permission-text">Lire les conversations</span>
              </label>
              <label class="permission-toggle">
                <input
                  type="checkbox"
                  class="permission-checkbox"
                  :checked="cohost.can_reply_messages"
                  @change="togglePermission(cohost, 'can_reply_messages')"
                />
                <span class="permission-text">Répondre</span>
              </label>
              <label class="permission-toggle">
                <input
                  type="checkbox"
                  class="permission-checkbox"
                  :checked="cohost.can_edit_listings"
                  @change="togglePermission(cohost, 'can_edit_listings')"
                />
                <span class="permission-text">Modifier annonces</span>
              </label>
            </div>
          </article>
        </div>
      </div>
    </div>
  </section>
</template>

<style scoped>
.cohosts-view {
  display: flex;
  flex-direction: column;
  gap: 2rem;
}

.header {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.title {
  font-size: 1.875rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.subtitle {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.content-grid {
  display: grid;
  gap: 1.5rem;
}

@media (min-width: 1024px) {
  .content-grid {
    grid-template-columns: 1fr 1.4fr;
  }
}

/* Form Card */
.form-card {
  display: flex;
  flex-direction: column;
  gap: 1.25rem;
  padding: 1.5rem;
  border-radius: 1.5rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  box-shadow: var(--shadow-sm);
  height: fit-content;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.5rem;
}

.form-label {
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  color: var(--color-text-tertiary);
}

/* Select Wrapper - Style Airbnb */
.select-wrapper {
  position: relative;
}

.select-icon-left {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: var(--color-text-tertiary);
}

.select-icon-right {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: var(--color-text-tertiary);
}

.icon {
  width: 1.25rem;
  height: 1.25rem;
}

.form-select {
  width: 100%;
  padding: 0.75rem 2.5rem 0.75rem 3rem;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  font-size: 0.875rem;
  cursor: pointer;
  appearance: none;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.form-select:hover {
  border-color: var(--color-text-tertiary);
}

.form-select:focus {
  outline: none;
  border-color: var(--color-text-primary);
  box-shadow: 0 0 0 2px var(--color-text-primary);
}

/* Input Wrapper */
.input-wrapper {
  position: relative;
}

.input-icon-left {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: var(--color-text-tertiary);
}

.form-input {
  width: 100%;
  padding: 0.75rem 1rem 0.75rem 3rem;
  border-radius: 0.75rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-primary);
  color: var(--color-text-primary);
  font-size: 0.875rem;
  transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.form-input::placeholder {
  color: var(--color-text-tertiary);
}

.form-input:hover {
  border-color: var(--color-text-tertiary);
}

.form-input:focus {
  outline: none;
  border-color: var(--color-text-primary);
  box-shadow: 0 0 0 2px var(--color-text-primary);
}

/* Permissions */
.permissions-list {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.permission-item {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  cursor: pointer;
}

.permission-checkbox {
  width: 1.125rem;
  height: 1.125rem;
  border-radius: 0.25rem;
  border: 2px solid var(--color-border-primary);
  cursor: pointer;
  accent-color: var(--color-brand-primary);
}

.permission-text {
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

/* Submit Button */
.submit-btn {
  width: 100%;
  padding: 0.75rem 1rem;
  border-radius: 0.75rem;
  background-color: var(--color-text-primary);
  color: var(--color-text-inverse);
  font-size: 0.875rem;
  font-weight: 600;
  border: none;
  cursor: pointer;
  transition: background-color var(--transition-fast), transform var(--transition-fast);
}

.submit-btn:hover:not(:disabled) {
  background-color: var(--color-brand-primary);
  transform: translateY(-1px);
}

.submit-btn:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

/* Cohosts List */
.cohosts-list {
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

.loading-card {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 1.5rem;
  border-radius: 1rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
}

.spinner {
  width: 1.25rem;
  height: 1.25rem;
  border: 2px solid var(--color-border-primary);
  border-top-color: var(--color-brand-primary);
  border-radius: 50%;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

.empty-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.75rem;
  padding: 2rem;
  border-radius: 1rem;
  border: 1px dashed var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  font-size: 0.875rem;
  color: var(--color-text-secondary);
  text-align: center;
}

.empty-icon {
  width: 3rem;
  height: 3rem;
  color: var(--color-text-tertiary);
}

.cohosts-cards {
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.cohost-card {
  padding: 1.25rem;
  border-radius: 1rem;
  border: 1px solid var(--color-border-primary);
  background-color: var(--color-bg-elevated);
  box-shadow: var(--shadow-sm);
  transition: box-shadow var(--transition-fast);
}

.cohost-card:hover {
  box-shadow: var(--shadow-md);
}

.cohost-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
}

.cohost-info {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}

.cohost-name {
  font-size: 0.875rem;
  font-weight: 600;
  color: var(--color-text-primary);
}

.cohost-email {
  font-size: 0.75rem;
  color: var(--color-text-secondary);
}

.cohost-listing {
  font-size: 0.75rem;
  color: var(--color-text-tertiary);
}

.delete-btn {
  font-size: 0.75rem;
  font-weight: 600;
  color: var(--color-error);
  background: none;
  border: none;
  cursor: pointer;
  transition: opacity var(--transition-fast);
}

.delete-btn:hover {
  opacity: 0.7;
}

.cohost-permissions {
  display: grid;
  gap: 0.75rem;
  margin-top: 1rem;
  padding-top: 1rem;
  border-top: 1px solid var(--color-border-secondary);
}

@media (min-width: 768px) {
  .cohost-permissions {
    grid-template-columns: repeat(3, 1fr);
  }
}

.permission-toggle {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  cursor: pointer;
}
</style>
