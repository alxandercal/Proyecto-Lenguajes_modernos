<template>
  <article class="panel flex flex-col gap-3 !p-5 transition hover:border-primary/50 hover:shadow-sm">
    <div class="flex items-start justify-between gap-3">
      <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-wide text-primary">
          {{ label('categories', donation.category) }}
        </p>
        <h3 class="truncate text-lg font-bold">{{ donation.title }}</h3>
        <p class="truncate text-sm opacity-70">{{ donation.donorName }}</p>
      </div>
      <div
        v-if="donation.priority"
        class="radial-progress shrink-0 text-sm font-black text-accent"
        :style="`--value:${donation.priority.score}; --size:3rem; --thickness:4px`"
        role="progressbar"
        :aria-label="`Prioridad ${donation.priority.score}`"
      >
        {{ donation.priority.score }}
      </div>
      <StatusBadge v-else :status="donation.status" />
    </div>

    <div class="flex flex-wrap gap-2 text-xs">
      <span class="badge badge-outline">{{ formatQuantity(donation.quantity, donation.unit) }}</span>
      <span v-if="donation.unit !== 'KG'" class="badge badge-ghost">≈ {{ formatKg(donation.quantityKg) }}</span>
      <span class="badge badge-ghost">{{ label('zones', donation.zone) }}</span>
      <span class="badge" :class="isUrgent(donation.expiresAt) ? 'badge-accent' : 'badge-ghost'">
        {{ timeLeft(donation.expiresAt) }}
      </span>
    </div>

    <details v-if="donation.priority" class="text-sm">
      <summary class="cursor-pointer text-xs font-semibold text-primary">¿Por qué está en este lugar?</summary>
      <div class="mt-3"><PriorityBreakdown :priority="donation.priority" /></div>
    </details>

    <NuxtLink :to="`/donations/${donation.id}`" class="btn btn-sm btn-primary btn-outline mt-auto">
      Ver detalle
    </NuxtLink>
  </article>
</template>

<script setup lang="ts">
import type { Donation } from '~/types'

// Tarjeta de una donación en el listado.
defineProps<{ donation: Donation }>()
const { label } = useCatalog()
</script>
