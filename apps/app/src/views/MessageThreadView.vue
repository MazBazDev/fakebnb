<script setup lang="ts">
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import Matter from 'matter-js'
import type { Body } from 'matter-js'
import { fetchMessages, sendMessage, type Message } from '@/services/messages'
import { getEcho } from '@/services/echo'
import { useAuthStore } from '@/stores/auth'
import { PageHeader, LoadingSpinner, AlertMessage } from '@/components/ui'

/** Extended Matter.js Body with scroll offset for physics animation */
interface MatterBodyWithScroll extends Body {
  scrollOffset?: number
}

const route = useRoute()
const messages = ref<Message[]>([])
const isLoading = ref(false)
const isSubmitting = ref(false)
const error = ref<string | null>(null)
const form = ref({ body: '' })
const channelName = ref<string | null>(null)
const threadRef = ref<HTMLDivElement | null>(null)
const canvasContainerRef = ref<HTMLDivElement | null>(null)
const auth = useAuthStore()
const currentUserId = computed(() => auth.user?.id ?? null)
const canReply = ref(true)
const isDropActive = ref(false)

// Matter.js variables
let engine: Matter.Engine | null = null
let render: Matter.Render | null = null
let runner: Matter.Runner | null = null
let mouseConstraint: Matter.MouseConstraint | null = null
let elementBodies: Array<{ elem: HTMLElement; body: Matter.Body }> = []
let animationFrameId: number | null = null

const breadcrumbs = computed(() => {
  if (route.meta.layout === 'host') {
    const listingId = route.query.listing
    return [
      { label: 'Hôte', to: '/host' },
      { label: 'Annonces', to: '/host/listings' },
      { label: 'Messagerie', to: listingId ? `/host/listings/${listingId}/messages` : '/host/listings' },
      { label: 'Conversation' },
    ]
  }

  return [
    { label: 'Accueil', to: '/' },
    { label: 'Messagerie', to: '/messages' },
    { label: 'Conversation' },
  ]
})

async function load() {
  isLoading.value = true
  error.value = null

  try {
    const id = Number(route.params.id)
    const response = await fetchMessages(id)
    messages.value = response.data.slice().reverse()
    canReply.value = response.meta?.can_reply ?? true
    await nextTick()
    setTimeout(() => {
      scrollToBottom()
    }, 100)
  } catch (err) {
    error.value = err instanceof Error ? err.message : 'Impossible de charger les messages.'
  } finally {
    isLoading.value = false
  }
}

function scrollToBottom() {
  if (!threadRef.value) return
  threadRef.value.scrollTop = threadRef.value.scrollHeight
}

function appendMessage(message: Message) {
  if (messages.value.find((item) => item.id === message.id)) {
    return
  }
  messages.value = [...messages.value, message]
  nextTick().then(scrollToBottom)
}

async function startPhysics() {
  if (!threadRef.value || !canvasContainerRef.value) return

  const { Engine, Render, World, Bodies, Runner, Mouse, MouseConstraint } = Matter

  await nextTick()

  const containerRect = threadRef.value.getBoundingClientRect()
  const width = threadRef.value.offsetWidth
  const height = threadRef.value.offsetHeight

  if (width <= 0 || height <= 0) return

  // Configurer le container
  threadRef.value.style.position = 'relative'
  threadRef.value.style.overflow = 'hidden'

  engine = Engine.create()
  engine.world.gravity.y = 1

  render = Render.create({
    element: canvasContainerRef.value,
    engine,
    options: {
      width,
      height,
      background: 'transparent',
      wireframes: false,
    },
  })

  const boundaryOptions = {
    isStatic: true,
    render: { fillStyle: 'transparent' },
  }

  // Le sol est au bas du container visible
  const floor = Bodies.rectangle(width / 2, height - 10, width, 20, boundaryOptions)
  const leftWall = Bodies.rectangle(10, height / 2, 20, height, boundaryOptions)
  const rightWall = Bodies.rectangle(width - 10, height / 2, 20, height, boundaryOptions)
  const ceiling = Bodies.rectangle(width / 2, 10, width, 20, boundaryOptions)

  // Cacher les labels "Vous" / noms des utilisateurs
  const labels = threadRef.value.querySelectorAll('.drop-label') as NodeListOf<HTMLElement>
  labels.forEach((label) => {
    label.style.display = 'none'
  })

  // Sélectionner tous les messages
  const elements = threadRef.value.querySelectorAll('.drop-element') as NodeListOf<HTMLElement>
  const scrollTop = threadRef.value.scrollTop

  elementBodies = Array.from(elements).map((elem) => {
    const rect = elem.getBoundingClientRect()

    // Position relative au container visible
    const x = rect.left - containerRect.left + rect.width / 2
    const y = rect.top - containerRect.top + rect.height / 2

    const body = Bodies.rectangle(x, y, rect.width, rect.height, {
      render: { fillStyle: 'transparent' },
      restitution: 0.8,
      frictionAir: 0.01,
      friction: 0.2,
    })

    Matter.Body.setVelocity(body, {
      x: (Math.random() - 0.5) * 5,
      y: 0,
    })
    Matter.Body.setAngularVelocity(body, (Math.random() - 0.5) * 0.05)

    // Stocker le scrollTop pour l'utiliser lors du positionnement
    ;(body as MatterBodyWithScroll).scrollOffset = scrollTop

    return { elem, body }
  })

  // Positionner les éléments en fixed pour garder leur position visuelle
  elementBodies.forEach(({ elem, body }) => {
    elem.style.position = 'fixed'
    elem.style.left = `${containerRect.left + body.position.x - (body.bounds.max.x - body.bounds.min.x) / 2}px`
    elem.style.top = `${containerRect.top + body.position.y - (body.bounds.max.y - body.bounds.min.y) / 2}px`
    elem.style.transform = 'none'
    elem.style.margin = '0'
    elem.style.zIndex = '1000'
  })

  const mouse = Mouse.create(threadRef.value)
  mouseConstraint = MouseConstraint.create(engine, {
    mouse,
    constraint: {
      stiffness: 0.2,
      render: { visible: false },
    },
  })
  render.mouse = mouse

  World.add(engine.world, [
    floor,
    leftWall,
    rightWall,
    ceiling,
    mouseConstraint,
    ...elementBodies.map((eb) => eb.body),
  ])

  runner = Runner.create()
  Runner.run(runner, engine)
  Render.run(render)

  // Stocker containerRect pour l'update loop
  const containerLeft = containerRect.left
  const containerTop = containerRect.top

  const updateLoop = () => {
    elementBodies.forEach(({ body, elem }) => {
      const { x, y } = body.position
      elem.style.left = `${containerLeft + x}px`
      elem.style.top = `${containerTop + y}px`
      elem.style.transform = `translate(-50%, -50%) rotate(${body.angle}rad)`
    })
    Matter.Engine.update(engine!)
    animationFrameId = requestAnimationFrame(updateLoop)
  }
  updateLoop()
}

function cleanupPhysics() {
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId)
    animationFrameId = null
  }

  if (render) {
    Matter.Render.stop(render)
    if (render.canvas && canvasContainerRef.value) {
      canvasContainerRef.value.removeChild(render.canvas)
    }
    render = null
  }

  if (runner && engine) {
    Matter.Runner.stop(runner)
    runner = null
  }

  if (engine) {
    Matter.World.clear(engine.world, false)
    Matter.Engine.clear(engine)
    engine = null
  }

  mouseConstraint = null
  elementBodies = []
}

async function submit() {
  error.value = null
  isSubmitting.value = true

  try {
    const id = Number(route.params.id)
    const messageBody = form.value.body
    const shouldDrop = messageBody.toLowerCase().trim() === 'drop'

    const message = await sendMessage(id, messageBody)
    appendMessage(message)
    form.value.body = ''

    if (shouldDrop && !isDropActive.value) {
      isDropActive.value = true
      await nextTick()
      setTimeout(() => {
        startPhysics()
      }, 100)
    }
  } catch (err) {
    error.value = err instanceof Error ? err.message : "Impossible d'envoyer le message."
  } finally {
    isSubmitting.value = false
  }
}

onMounted(load)
onMounted(() => {
  const id = Number(route.params.id)
  channelName.value = `conversation.${id}`
  const echo = getEcho()

  echo.private(channelName.value).listen('.MessageCreated', (payload: Message) => {
    appendMessage(payload)
  })
})

onUnmounted(() => {
  const echo = getEcho()
  if (channelName.value) {
    echo.leave(channelName.value)
  }
  cleanupPhysics()
})
</script>

<template>
  <section class="flex h-[calc(100vh-160px)] flex-col gap-4">
    <PageHeader
      title="Conversation"
      subtitle="Fil de discussion"
      :breadcrumbs="breadcrumbs"
    />

    <AlertMessage v-if="error" :message="error" type="error" />

    <LoadingSpinner v-if="isLoading" text="Chargement des messages..." full-container />

    <template v-else>
    <div
      ref="threadRef"
      class="h-[400px] space-y-4 overflow-y-auto rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
    >
      <div ref="canvasContainerRef" class="pointer-events-none absolute left-0 top-0 z-0" />

      <div
        v-for="message in messages"
        :key="message.id"
        class="flex flex-col"
        :class="message.sender_user_id === currentUserId ? 'items-end' : 'items-start'"
      >
        <div
          class="drop-element max-w-[70%] rounded-2xl px-4 py-3 text-sm shadow-sm"
            :class="
              message.sender_user_id === currentUserId
                ? 'bg-[#222222] text-white'
                : 'bg-gray-100 text-[#222222]'
            "
        >
          {{ message.body }}
        </div>
        <p class="drop-label mt-1 text-xs text-gray-400">
          {{
            message.sender_user_id === currentUserId
              ? 'Vous'
              : message.sender?.name ?? 'Utilisateur'
          }}
        </p>
      </div>
    </div>

    <form
      class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm"
      @submit.prevent="submit"
    >
      <AlertMessage
        v-if="!canReply"
        message="Vous n'avez pas la permission d'envoyer des messages pour cette annonce."
        type="warning"
        class="mb-3"
      />
      <div class="flex flex-col gap-3 md:flex-row md:items-end">
        <div class="flex-1">
          <label for="message-body" class="block text-sm font-semibold text-[#222222]">
            Nouveau message
          </label>
          <textarea
            id="message-body"
            v-model="form.body"
            rows="2"
            class="mt-2 w-full rounded-lg border border-gray-300 px-4 py-3 text-base text-[#222222] transition placeholder:text-gray-400 focus:border-black focus:outline-none focus:ring-2 focus:ring-black"
            placeholder="Écrivez votre message..."
            :disabled="!canReply"
            required
          ></textarea>
        </div>
        <button
          class="rounded-lg bg-gradient-to-r from-[#E61E4D] to-[#D70466] px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:shadow-md disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="isSubmitting || !canReply"
          type="submit"
        >
          {{ isSubmitting ? 'Envoi...' : 'Envoyer' }}
        </button>
      </div>
    </form>
    </template>
  </section>
</template>
