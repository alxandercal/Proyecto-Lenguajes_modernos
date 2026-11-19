<template>
  <div class="space-y-5">
    <NuxtLink to="/" class="link link-primary text-sm">← Donaciones</NuxtLink>

    <div v-if="loading" class="skeleton h-72 w-full" />
    <div v-else-if="error && !donation" role="alert" class="alert alert-error">{{ error }}</div>

    <div v-else-if="donation" class="grid gap-5 lg:grid-cols-3">
      <section class="panel space-y-4 lg:col-span-2">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-primary">
              {{ label('categories', donation.category) }}
            </p>
            <h1 class="page-title">{{ donation.title }}</h1>
            <p class="opacity-70">{{ donation.donorName }}</p>
          </div>
          <StatusBadge :status="donation.status" />
        </div>

        <dl class="grid gap-3 sm:grid-cols-2">
          <div class="rounded-box bg-base-200 p-3">
            <dt class="text-xs opacity-60">Cantidad</dt>
            <dd class="font-bold">
              {{ formatQuantity(donation.quantity, donation.unit) }}
              <span v-if="donation.unit !== 'KG'" class="text-sm font-normal opacity-60">
                ≈ {{ formatKg(donation.quantityKg) }}
              </span>
            </dd>
          </div>
          <div class="rounded-box bg-base-200 p-3">
            <dt class="text-xs opacity-60">Vencimiento</dt>
            <dd class="font-bold">{{ formatDate(donation.expiresAt) }}</dd>
            <dd
              v-if="donation.status === 'PUBLISHED' || donation.status === 'RESERVED'"
              class="text-sm"
              :class="isUrgent(donation.expiresAt) ? 'text-accent' : 'opacity-60'"
            >
              {{ timeLeft(donation.expiresAt) }}
            </dd>
          </div>
          <div class="rounded-box bg-base-200 p-3 sm:col-span-2">
            <dt class="text-xs opacity-60">Punto de recogida</dt>
            <dd class="font-bold">{{ donation.pickupAddress }}</dd>
            <dd class="text-sm opacity-60">{{ label('zones', donation.zone) }}</dd>
          </div>
        </dl>
        <p v-if="donation.notes" class="rounded-box border border-base-300 p-3 text-sm">
          <span class="font-semibold">Notas:</span> {{ donation.notes }}
        </p>
        <p v-if="donation.reservedByName" class="text-sm">
          Reservada por <span class="font-semibold">{{ donation.reservedByName }}</span>
        </p>

        <p v-if="error" role="alert" class="alert alert-error text-sm">{{ error }}</p>
      </section>

      <aside class="space-y-5">
        <section v-if="donation.priority && donation.status === 'PUBLISHED'" class="panel">
          <h2 class="mb-3 font-bold">¿Qué tan urgente es?</h2>
          <PriorityBreakdown :priority="donation.priority" />
        </section>
        <section class="panel">
          <h2 class="mb-3 font-bold">Historial</h2>
          <HistoryTimeline :history="donation.history" />
        </section>
      </aside>
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Donation } from '~/types'
import { useAuthStore } from '~/stores/auth'

// Detalle de una donación. En la R2 se agregan los botones para reservar y liberar.
const route = useRoute()
const auth = useAuthStore()
const { request } = useApi()
const { load: loadCatalog, label } = useCatalog()
const id = String(route.params.id)

const donation = ref<Donation | null>(null)
const loading = ref(true)
const error = ref('')

async function load() {
  try {
    donation.value = await request<Donation>(`/donations/${id}`)
  } catch (e) {
    error.value = (e as Error).message
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await auth.whenReady()
  await loadCatalog().catch(() => null)
  await load()
})
</script>
