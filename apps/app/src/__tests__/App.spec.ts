import { describe, it, expect } from 'vitest'

import { mount } from '@vue/test-utils'
import { createPinia } from 'pinia'
import router from '@/router'
import App from '@/App.vue'

describe('App', () => {
  it('mounts renders properly', async () => {
    const pinia = createPinia()

    const wrapper = mount(App, {
      global: {
        plugins: [pinia, router],
      },
    })

    router.push('/')
    await router.isReady()

    expect(wrapper.text()).toContain('MiniBnB')
  })
})
