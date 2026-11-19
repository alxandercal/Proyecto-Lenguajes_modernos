<template>
  <div class="space-y-3">
    <div class="flex items-center gap-3">
      <div
        class="radial-progress shrink-0 text-sm font-black"
        :class="scoreColor"
        :style="`--value:${priority.score}; --size:3.25rem; --thickness:5px`"
        role="progressbar"
        :aria-valuenow="priority.score"
      >
        {{ priority.score }}
      </div>
      <div class="text-sm leading-tight">
        <p class="font-bold">Prioridad {{ priority.score }} de 100</p>
        <p class="opacity-70">Suma de urgencia, cercanía y volumen</p>
      </div>
    </div>
    <ul class="space-y-2">
      <li v-for="factor in priority.factors" :key="factor.key">
        <div class="flex justify-between text-xs">
          <span class="font-semibold">{{ factor.label }}</span>
          <span class="opacity-70">{{ factor.points }} de {{ factor.max }}</span>
        </div>
        <progress
          class="progress h-2 w-full"
          :class="barColor[factor.key]"
          :value="factor.points"
          :max="factor.max"
        />
        <p class="text-xs opacity-60">{{ factor.detail }}</p>
      </li>
    </ul>
  </div>
</template>

<script setup lang="ts">
import type { Priority } from '~/types'

// Componente innovador en pantalla: muestra por qué una donación tiene su puntaje.
const props = defineProps<{ priority: Priority }>()

const barColor = { urgency: 'progress-accent', proximity: 'progress-primary', volume: 'progress-secondary' }
const scoreColor = computed(() => {
  if (props.priority.score >= 70) return 'text-accent'
  if (props.priority.score >= 40) return 'text-secondary'
  return 'text-primary'
})
</script>
