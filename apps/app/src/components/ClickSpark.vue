<script setup lang="ts">
import { ref, onMounted, onUnmounted } from 'vue'

interface Spark {
  id: number
  x: number
  y: number
  angle: number
  velocity: number
  life: number
  maxLife: number
}

const props = withDefaults(
  defineProps<{
    count?: number
    colors?: string[]
    size?: number
    duration?: number
  }>(),
  {
    count: 8,
    colors: () => ['#ff385c', '#e61e4d', '#ffd700', '#ff6b6b', '#4ecdc4'],
    size: 4,
    duration: 800,
  }
)

const containerRef = ref<HTMLElement | null>(null)
const sparks = ref<Spark[]>([])
let sparkId = 0
let animationFrameId: number | null = null

function createSpark(x: number, y: number): Spark {
  const angle = (Math.PI * 2 * Math.random())
  const velocity = 2 + Math.random() * 3

  return {
    id: sparkId++,
    x,
    y,
    angle,
    velocity,
    life: props.duration,
    maxLife: props.duration,
  }
}

function handleClick(event: MouseEvent) {
  if (!containerRef.value) return

  const rect = containerRef.value.getBoundingClientRect()
  const x = event.clientX - rect.left
  const y = event.clientY - rect.top

  const newSparks: Spark[] = []
  for (let i = 0; i < props.count; i++) {
    newSparks.push(createSpark(x, y))
  }

  sparks.value.push(...newSparks)

  if (!animationFrameId) {
    animate()
  }
}

function animate() {
  sparks.value = sparks.value
    .map((spark) => {
      const progress = 1 - spark.life / spark.maxLife
      const distance = spark.velocity * (1 - progress * 0.5)

      return {
        ...spark,
        x: spark.x + Math.cos(spark.angle) * distance,
        y: spark.y + Math.sin(spark.angle) * distance + progress * 2,
        life: spark.life - 16,
      }
    })
    .filter((spark) => spark.life > 0)

  if (sparks.value.length > 0) {
    animationFrameId = requestAnimationFrame(animate)
  } else {
    animationFrameId = null
  }
}

onMounted(() => {
  if (containerRef.value) {
    containerRef.value.addEventListener('click', handleClick)
  }
})

onUnmounted(() => {
  if (containerRef.value) {
    containerRef.value.removeEventListener('click', handleClick)
  }
  if (animationFrameId) {
    cancelAnimationFrame(animationFrameId)
  }
})

function getSparkColor(index: number): string {
  return props.colors[index % props.colors.length] || '#ff385c'
}

function getSparkOpacity(spark: Spark): number {
  return spark.life / spark.maxLife
}
</script>

<template>
  <div ref="containerRef" class="click-spark-container">
    <slot />
    <svg
      class="click-spark-overlay"
      :style="{
        position: 'absolute',
        top: 0,
        left: 0,
        width: '100%',
        height: '100%',
        pointerEvents: 'none',
        overflow: 'visible',
      }"
    >
      <circle
        v-for="(spark, index) in sparks"
        :key="spark.id"
        :cx="spark.x"
        :cy="spark.y"
        :r="size * (spark.life / spark.maxLife)"
        :fill="getSparkColor(index)"
        :opacity="getSparkOpacity(spark)"
      />
    </svg>
  </div>
</template>

<style scoped>
.click-spark-container {
  position: relative;
  display: inline-block;
}

.click-spark-overlay {
  z-index: 9999;
}
</style>
