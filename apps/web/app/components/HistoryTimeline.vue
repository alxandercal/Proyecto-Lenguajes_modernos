<template>
  <ul class="timeline timeline-vertical timeline-compact">
    <li v-for="(entry, index) in history" :key="index">
      <hr v-if="index > 0" class="bg-base-300" />
      <div class="timeline-middle">
        <span class="grid size-6 place-items-center rounded-full text-xs" :class="info(entry.action).css">
          {{ info(entry.action).icon }}
        </span>
      </div>
      <div class="timeline-end mb-4 ml-2">
        <p class="text-sm font-semibold">{{ info(entry.action).text }}</p>
        <p class="text-xs opacity-60">{{ formatDate(entry.at) }} · {{ entry.byName }}</p>
        <p v-if="entry.note" class="text-xs italic opacity-80">“{{ entry.note }}”</p>
      </div>
      <hr v-if="index < history.length - 1" class="bg-base-300" />
    </li>
  </ul>
</template>

<script setup lang="ts">
import type { HistoryEntry } from '~/types'

// Línea de tiempo con cada movimiento de la donación.
defineProps<{ history: HistoryEntry[] }>()

const steps: Record<string, { text: string; icon: string; css: string }> = {
  PUBLISHED: { text: 'Publicada', icon: '✓', css: 'bg-success text-success-content' },
  EDITED: { text: 'Editada', icon: '✎', css: 'bg-base-300' },
  RESERVED: { text: 'Reservada', icon: '★', css: 'bg-warning text-warning-content' },
  RELEASED: { text: 'Reserva liberada', icon: '↺', css: 'bg-base-300' },
  DELIVERED: { text: 'Entregada', icon: '✔', css: 'bg-info text-info-content' },
  CANCELLED: { text: 'Cancelada', icon: '✕', css: 'bg-error text-error-content' }
}
const info = (action: string) => steps[action] ?? { text: action, icon: '•', css: 'bg-base-300' }
</script>
