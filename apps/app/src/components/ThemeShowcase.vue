<script setup lang="ts">
import { useTheme } from '@/composables/useTheme'

const { theme, resolvedTheme, systemPreference, isDark, setTheme, toggleTheme, cycleTheme } = useTheme()

const themeOptions = [
  { value: 'light', label: '☀️ Clair', icon: 'sun' },
  { value: 'dark', label: '🌙 Sombre', icon: 'moon' },
  { value: 'system', label: '💻 Système', icon: 'system' },
] as const

const colorTokens = [
  { name: 'Background Primary', var: '--color-bg-primary', category: 'Backgrounds' },
  { name: 'Background Secondary', var: '--color-bg-secondary', category: 'Backgrounds' },
  { name: 'Background Tertiary', var: '--color-bg-tertiary', category: 'Backgrounds' },
  { name: 'Text Primary', var: '--color-text-primary', category: 'Text' },
  { name: 'Text Secondary', var: '--color-text-secondary', category: 'Text' },
  { name: 'Text Tertiary', var: '--color-text-tertiary', category: 'Text' },
  { name: 'Border Primary', var: '--color-border-primary', category: 'Borders' },
  { name: 'Brand Primary', var: '--color-brand-primary', category: 'Brand' },
  { name: 'Success', var: '--color-success', category: 'States' },
  { name: 'Warning', var: '--color-warning', category: 'States' },
  { name: 'Error', var: '--color-error', category: 'States' },
  { name: 'Info', var: '--color-info', category: 'States' },
]

const groupedTokens = colorTokens.reduce((acc, token) => {
  if (!acc[token.category]) {
    acc[token.category] = []
  }
  acc[token.category]!.push(token)
  return acc
}, {} as Record<string, Array<typeof colorTokens[number]>>)
</script>

<template>
  <div class="theme-showcase">
    <div class="showcase-container">
      <!-- Header -->
      <div class="showcase-header">
        <h1 class="showcase-title">🎨 Système de Thème FakeBnB</h1>
        <p class="showcase-description">
          Architecture robuste avec tokens sémantiques et support automatique du mode sombre
        </p>
      </div>

      <!-- Theme Selector -->
      <div class="section">
        <h2 class="section-title">Sélection du thème</h2>

        <div class="theme-selector">
          <button
            v-for="option in themeOptions"
            :key="option.value"
            class="theme-option"
            :class="{ active: theme === option.value }"
            @click="setTheme(option.value)"
          >
            <span class="theme-option-label">{{ option.label }}</span>
            <span v-if="theme === option.value" class="theme-option-check">✓</span>
          </button>
        </div>

        <div class="theme-actions">
          <button class="action-btn" @click="toggleTheme">
            <span>🔄</span> Toggle (Light ↔ Dark)
          </button>
          <button class="action-btn" @click="cycleTheme">
            <span>🔁</span> Cycle (Light → Dark → System)
          </button>
        </div>
      </div>

      <!-- Theme Info -->
      <div class="section">
        <h2 class="section-title">État actuel</h2>

        <div class="info-grid">
          <div class="info-card">
            <div class="info-label">Thème sélectionné</div>
            <div class="info-value">{{ theme }}</div>
          </div>

          <div class="info-card">
            <div class="info-label">Thème résolu</div>
            <div class="info-value">{{ resolvedTheme }}</div>
          </div>

          <div class="info-card">
            <div class="info-label">Préférence système</div>
            <div class="info-value">{{ systemPreference }}</div>
          </div>

          <div class="info-card">
            <div class="info-label">Mode sombre actif</div>
            <div class="info-value">{{ isDark ? 'Oui ✓' : 'Non ✗' }}</div>
          </div>
        </div>
      </div>

      <!-- Color Tokens -->
      <div class="section">
        <h2 class="section-title">Design Tokens (Couleurs)</h2>

        <div v-for="(tokens, category) in groupedTokens" :key="category" class="token-group">
          <h3 class="token-category">{{ category }}</h3>
          <div class="token-grid">
            <div
              v-for="token in tokens"
              :key="token.var"
              class="token-card"
            >
              <div
                class="token-swatch"
                :style="{ backgroundColor: `var(${token.var})` }"
              ></div>
              <div class="token-info">
                <div class="token-name">{{ token.name }}</div>
                <code class="token-var">var({{ token.var }})</code>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Component Examples -->
      <div class="section">
        <h2 class="section-title">Composants de démonstration</h2>

        <div class="examples-grid">
          <!-- Card Example -->
          <div class="example-card">
            <h3 class="example-title">Card</h3>
            <div class="demo-content">Contenu de la carte</div>
          </div>

          <!-- Button Examples -->
          <div class="example-buttons">
            <button class="demo-btn demo-btn-primary">Bouton Principal</button>
            <button class="demo-btn demo-btn-secondary">Bouton Secondaire</button>
            <button class="demo-btn demo-btn-outline">Bouton Outline</button>
          </div>

          <!-- Input Example -->
          <div class="example-input">
            <label class="demo-label">Champ de texte</label>
            <input
              type="text"
              class="demo-input"
              placeholder="Entrez du texte..."
            />
          </div>

          <!-- Alert Examples -->
          <div class="example-alerts">
            <div class="demo-alert demo-alert-success">
              ✓ Succès : Opération réussie
            </div>
            <div class="demo-alert demo-alert-warning">
              ⚠️ Attention : Vérifiez les informations
            </div>
            <div class="demo-alert demo-alert-error">
              ✗ Erreur : Une erreur s'est produite
            </div>
            <div class="demo-alert demo-alert-info">
              ℹ️ Info : Information importante
            </div>
          </div>
        </div>
      </div>

      <!-- Typography -->
      <div class="section">
        <h2 class="section-title">Typographie</h2>

        <div class="typography-examples">
          <h1 class="text-primary" style="font-size: var(--font-size-3xl); font-weight: var(--font-weight-bold)">
            Titre H1 - 2rem
          </h1>
          <h2 class="text-primary" style="font-size: var(--font-size-2xl); font-weight: var(--font-weight-semibold)">
            Titre H2 - 1.5rem
          </h2>
          <h3 class="text-primary" style="font-size: var(--font-size-xl); font-weight: var(--font-weight-semibold)">
            Titre H3 - 1.25rem
          </h3>
          <p class="text-primary" style="font-size: var(--font-size-base)">
            Paragraphe normal - 1rem (16px)
          </p>
          <p class="text-secondary" style="font-size: var(--font-size-sm)">
            Texte secondaire - 0.875rem (14px)
          </p>
          <p class="text-tertiary" style="font-size: var(--font-size-xs)">
            Texte tertiaire - 0.75rem (12px)
          </p>
        </div>
      </div>

      <!-- Shadows -->
      <div class="section">
        <h2 class="section-title">Ombres</h2>

        <div class="shadow-examples">
          <div class="shadow-box" style="box-shadow: var(--shadow-sm)">
            Shadow SM
          </div>
          <div class="shadow-box" style="box-shadow: var(--shadow-md)">
            Shadow MD
          </div>
          <div class="shadow-box" style="box-shadow: var(--shadow-lg)">
            Shadow LG
          </div>
          <div class="shadow-box" style="box-shadow: var(--shadow-xl)">
            Shadow XL
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.theme-showcase {
  min-height: 100vh;
  background-color: var(--color-bg-primary);
  padding: var(--spacing-2xl);
}

.showcase-container {
  max-width: 1200px;
  margin: 0 auto;
}

.showcase-header {
  text-align: center;
  margin-bottom: var(--spacing-3xl);
}

.showcase-title {
  color: var(--color-text-primary);
  font-size: var(--font-size-3xl);
  font-weight: var(--font-weight-bold);
  margin-bottom: var(--spacing-md);
}

.showcase-description {
  color: var(--color-text-secondary);
  font-size: var(--font-size-lg);
}

.section {
  background-color: var(--color-bg-elevated);
  border: 1px solid var(--color-border-primary);
  border-radius: var(--radius-lg);
  padding: var(--spacing-xl);
  margin-bottom: var(--spacing-xl);
  box-shadow: var(--shadow-card);
}

.section-title {
  color: var(--color-text-primary);
  font-size: var(--font-size-2xl);
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--spacing-lg);
  padding-bottom: var(--spacing-md);
  border-bottom: 2px solid var(--color-border-secondary);
}

/* Theme Selector */
.theme-selector {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-md);
  margin-bottom: var(--spacing-lg);
}

.theme-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: var(--spacing-lg);
  background-color: var(--color-bg-secondary);
  border: 2px solid var(--color-border-primary);
  border-radius: var(--radius-md);
  cursor: pointer;
  transition: all var(--transition-fast);
  font-size: var(--font-size-base);
  color: var(--color-text-primary);
}

.theme-option:hover {
  border-color: var(--color-brand-primary);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.theme-option.active {
  background-color: var(--color-brand-primary);
  color: white;
  border-color: var(--color-brand-primary);
}

.theme-option-label {
  font-weight: var(--font-weight-medium);
}

.theme-option-check {
  font-weight: var(--font-weight-bold);
  font-size: var(--font-size-lg);
}

.theme-actions {
  display: flex;
  gap: var(--spacing-md);
  flex-wrap: wrap;
}

.action-btn {
  padding: var(--spacing-md) var(--spacing-lg);
  background-color: var(--color-bg-tertiary);
  border: 1px solid var(--color-border-primary);
  border-radius: var(--radius-md);
  color: var(--color-text-primary);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.action-btn:hover {
  background-color: var(--color-brand-primary);
  color: white;
  border-color: var(--color-brand-primary);
  transform: scale(1.05);
}

/* Info Grid */
.info-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: var(--spacing-md);
}

.info-card {
  background-color: var(--color-bg-secondary);
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--radius-md);
  padding: var(--spacing-lg);
}

.info-label {
  color: var(--color-text-secondary);
  font-size: var(--font-size-sm);
  margin-bottom: var(--spacing-xs);
}

.info-value {
  color: var(--color-text-primary);
  font-size: var(--font-size-xl);
  font-weight: var(--font-weight-semibold);
  text-transform: capitalize;
}

/* Token Display */
.token-group {
  margin-bottom: var(--spacing-xl);
}

.token-category {
  color: var(--color-text-primary);
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--spacing-md);
}

.token-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
  gap: var(--spacing-md);
}

.token-card {
  display: flex;
  align-items: center;
  gap: var(--spacing-md);
  padding: var(--spacing-md);
  background-color: var(--color-bg-secondary);
  border: 1px solid var(--color-border-secondary);
  border-radius: var(--radius-sm);
}

.token-swatch {
  width: 48px;
  height: 48px;
  border-radius: var(--radius-sm);
  border: 1px solid var(--color-border-primary);
  flex-shrink: 0;
}

.token-info {
  flex: 1;
  min-width: 0;
}

.token-name {
  color: var(--color-text-primary);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
  margin-bottom: 2px;
}

.token-var {
  color: var(--color-text-tertiary);
  font-size: var(--font-size-xs);
  font-family: 'Monaco', 'Courier New', monospace;
  background-color: var(--color-bg-tertiary);
  padding: 2px 6px;
  border-radius: 4px;
}

/* Examples */
.examples-grid {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

.example-card {
  padding: var(--spacing-lg);
  background-color: var(--color-bg-secondary);
  border: 1px solid var(--color-border-primary);
  border-radius: var(--radius-lg);
  box-shadow: var(--shadow-card);
  transition: box-shadow var(--transition-base);
}

.example-card:hover {
  box-shadow: var(--shadow-card-hover);
}

.example-title {
  color: var(--color-text-primary);
  font-size: var(--font-size-lg);
  font-weight: var(--font-weight-semibold);
  margin-bottom: var(--spacing-md);
}

.demo-content {
  color: var(--color-text-secondary);
}

.example-buttons {
  display: flex;
  gap: var(--spacing-md);
  flex-wrap: wrap;
}

.demo-btn {
  padding: var(--spacing-md) var(--spacing-xl);
  border-radius: var(--radius-md);
  font-weight: var(--font-weight-medium);
  cursor: pointer;
  transition: all var(--transition-fast);
}

.demo-btn-primary {
  background-color: var(--color-brand-primary);
  color: white;
  border: none;
}

.demo-btn-primary:hover {
  background-color: var(--color-brand-primary-hover);
  transform: scale(1.05);
}

.demo-btn-secondary {
  background-color: var(--color-bg-tertiary);
  color: var(--color-text-primary);
  border: 1px solid var(--color-border-primary);
}

.demo-btn-secondary:hover {
  background-color: var(--color-bg-secondary);
  transform: scale(1.05);
}

.demo-btn-outline {
  background-color: transparent;
  color: var(--color-brand-primary);
  border: 2px solid var(--color-brand-primary);
}

.demo-btn-outline:hover {
  background-color: var(--color-brand-primary);
  color: white;
  transform: scale(1.05);
}

.example-input {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-sm);
}

.demo-label {
  color: var(--color-text-primary);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
}

.demo-input {
  padding: var(--spacing-md);
  background-color: var(--color-bg-primary);
  border: 1px solid var(--color-border-primary);
  border-radius: var(--radius-md);
  color: var(--color-text-primary);
  font-size: var(--font-size-base);
  transition: border-color var(--transition-fast);
}

.demo-input:focus {
  outline: none;
  border-color: var(--color-brand-primary);
}

.demo-input::placeholder {
  color: var(--color-text-tertiary);
}

.example-alerts {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-md);
}

.demo-alert {
  padding: var(--spacing-md) var(--spacing-lg);
  border-radius: var(--radius-md);
  font-size: var(--font-size-sm);
  font-weight: var(--font-weight-medium);
}

.demo-alert-success {
  background-color: var(--color-success-bg);
  color: var(--color-success);
  border: 1px solid var(--color-success);
}

.demo-alert-warning {
  background-color: var(--color-warning-bg);
  color: var(--color-warning);
  border: 1px solid var(--color-warning);
}

.demo-alert-error {
  background-color: var(--color-error-bg);
  color: var(--color-error);
  border: 1px solid var(--color-error);
}

.demo-alert-info {
  background-color: var(--color-info-bg);
  color: var(--color-info);
  border: 1px solid var(--color-info);
}

/* Typography */
.typography-examples {
  display: flex;
  flex-direction: column;
  gap: var(--spacing-lg);
}

/* Shadows */
.shadow-examples {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
  gap: var(--spacing-lg);
}

.shadow-box {
  padding: var(--spacing-xl);
  background-color: var(--color-bg-elevated);
  border-radius: var(--radius-md);
  text-align: center;
  color: var(--color-text-primary);
  font-weight: var(--font-weight-medium);
}
</style>
