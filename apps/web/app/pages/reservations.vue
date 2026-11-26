<template>
  <div class="space-y-5">
    <RoleNotice
      v-if="auth.ready && auth.role !== 'RECEIVER'"
      message="Esta sección es para organizaciones receptoras."
    />

    <template v-else>
      <div>
        <h1 class="page-title">Mis reservas</h1>
        <p class="opacity-70">{{ auth.profile?.organizationName }}</p>
      </div>

      <div class="grid grid-cols-2 gap-3 md:grid-cols-3">
        <div class="panel !p-4">
          <p class="text-xs opacity-60">Reservas</p>
          <p class="text-2xl font-black text-primary">{{ reservations.length }}</p>
        </div>
        <div class="panel !p-4">
          <p class="text-xs opacity-60">Recibidas</p>
          <p class="text-2xl font-black">{{ delivered.length }}</p>
        </div>
        <div class="panel !p-4">
          <p class="text-xs opacity-60">Por recoger</p>
          <p class="text-2xl font-black text-warning">{{ active.length }}</p>
        </div>
      </div>

      <div v-if="error" role="alert" class="alert alert-error">{{ error }}</div>
      <div v-if="loading" class="skeleton h-40 w-full" />
      <div v-else-if="!reservations.length" class="panel text-center">
        <p class="font-semibold">Todavía no reservas donaciones</p>
        <NuxtLink to="/" class="btn btn-primary btn-sm mt-3">Ver donaciones disponibles</NuxtLink>
      </div>

      <div v-else class="grid gap-4 md:grid-cols-2">
        <article v-for="item in reservations" :key="item.id" class="panel space-y-2 !p-5">
          <div class="flex items-start justify-between gap-2">
            <div>
              <h2 class="font-bold">{{ item.donationTitle }}</h2>
              <p class="text-sm opacity-70">{{ item.donorName }}</p>
            </div>
            <StatusBadge :status="item.status" />
          </div>
          <p class="text-sm">
            {{ formatQuantity(item.quantity, item.unit) }} · ≈ {{ formatKg(item.quantityKg) }}
          </p>
          <p class="text-sm">Recoger en {{ item.pickupAddress }} ({{ label('zones', item.zone) }})</p>
          <p v-if="item.status === 'ACTIVE'" class="text-sm" :class="isUrgent(item.expiresAt) ? 'text-accent' : ''">
            {{ timeLeft(item.expiresAt) }}
          </p>
          <div class="flex gap-2 pt-1">
            <NuxtLink :to="`/donations/${item.donationId}`" class="btn btn-sm btn-outline btn-primary">Ver</NuxtLink>
            <button v-if="item.status === 'ACTIVE'" class="btn btn-sm btn-ghost text-warning" @click="release(item)">
              Liberar
            </button>
          </div>
        </article>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { Reservation } from '~/types'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const { request } = useApi()
const { load: loadCatalog, label } = useCatalog()
const reservations = ref<Reservation[]>([])
const loading = ref(true)
const error = ref('')
const active = computed(() => reservations.value.filter(item => item.status === 'ACTIVE'))
const delivered = computed(() => reservations.value.filter(item => item.status === 'DELIVERED'))

async function load() {
  if (auth.role !== 'RECEIVER') return
  loading.value = true
  try {
    reservations.value = await request<Reservation[]>('/reservations/mine')
  } catch (e) {
    error.value = (e as Error).message
  } finally {
    loading.value = false
  }
}

async function release(item: Reservation) {
  if (!confirm(`¿Liberar "${item.donationTitle}"? Otra organización podrá reservarla.`)) return
  try {
    await request(`/reservations/${item.id}`, { method: 'DELETE' })
    await load()
  } catch (e) {
    error.value = (e as Error).message
  }
}

onMounted(async () => {
  await auth.whenReady()
  await loadCatalog().catch(() => null)
  await load()
})
</script>
