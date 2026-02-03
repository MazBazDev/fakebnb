import { globalIgnores } from 'eslint/config'
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript'
import pluginVue from 'eslint-plugin-vue'
import pluginVitest from '@vitest/eslint-plugin'
import pluginOxlint from 'eslint-plugin-oxlint'
import skipFormatting from 'eslint-config-prettier/flat'

// To allow more languages other than `ts` in `.vue` files, uncomment the following lines:
// import { configureVueProject } from '@vue/eslint-config-typescript'
// configureVueProject({ scriptLangs: ['ts', 'tsx'] })
// More info at https://github.com/vuejs/eslint-config-typescript/#advanced-setup

export default defineConfigWithVueTs(
  {
    name: 'app/files-to-lint',
    files: ['**/*.{vue,ts,mts,tsx}'],
  },

  globalIgnores(['**/dist/**', '**/dist-ssr/**', '**/coverage/**']),

  // Upgraded from 'flat/essential' to 'flat/recommended' for stricter Vue rules
  ...pluginVue.configs['flat/recommended'],
  vueTsConfigs.recommended,

  // Custom Vue rules for better code quality
  {
    name: 'app/vue-rules',
    files: ['**/*.vue'],
    rules: {
      // Enforce consistent attribute ordering
      'vue/attributes-order': ['warn', { alphabetical: false }],
      // Enforce component name casing
      'vue/component-name-in-template-casing': ['warn', 'PascalCase'],
      // Require explicit emits declaration
      'vue/require-explicit-emits': 'warn',
      // Enforce v-bind shorthand
      'vue/v-bind-style': ['warn', 'shorthand'],
      // Enforce v-on shorthand
      'vue/v-on-style': ['warn', 'shorthand'],
      // Allow v-html for flexibility (be careful with XSS)
      'vue/no-v-html': 'off',
      // Allow single-word component names (for views)
      'vue/multi-word-component-names': 'off',
    },
  },

  {
    ...pluginVitest.configs.recommended,
    files: ['src/**/__tests__/*'],
  },

  ...pluginOxlint.configs['flat/recommended'],

  skipFormatting,
)
