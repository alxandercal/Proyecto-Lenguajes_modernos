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

        <!-- Acciones según el rol y el estado -->
        <div class="space-y-3 border-t border-base-300 pt-4">
          <div v-if="error" role="alert" class="alert alert-error text-sm">{{ error }}</div>
          <div v-if="message" class="alert alert-success text-sm">{{ message }}</div>

          <template v-if="donation.status === 'PUBLISHED'">
            <button v-if="auth.role === 'RECEIVER'" class="btn btn-primary" :disabled="sending" @click="reserve">
              {{ sending ? 'Reservando...' : 'Reservar esta donación' }}
            </button>
            <p v-else-if="!auth.user" class="text-sm opacity-70">
              <NuxtLink to="/login" class="link link-primary">Inicia sesión</NuxtLink> como receptor para reservar.
            </p>
          </template>

          <template v-if="donation.status === 'RESERVED' && isReceiver">
            <p class="text-sm">Pasa a recoger al punto indicado. Si ya no puedes, libera la reserva.</p>
            <button class="btn btn-outline btn-warning" :disabled="sending" @click="release">Liberar reserva</button>
          </template>
        </div>
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
import type { Donation, Reservation } from '~/types'
import { ApiError } from '~/composables/useApi'
import { useAuthStore } from '~/stores/auth'

const route = useRoute()
const auth = useAuthStore()
const { request } = useApi()
const { load: loadCatalog, label } = useCatalog()
const id = String(route.params.id)

const donation = ref<Donation | null>(null)
const loading = ref(true)
const sending = ref(false)
const error = ref('')
const message = ref('')

const isReceiver = computed(() => donation.value?.reservedBy === auth.user?.uid)

async function load() {
  try {
    donation.value = await request<Donation>(`/donations/${id}`)
  } catch (e) {
    error.value = (e as Error).message
  } finally {
    loading.value = false
  }
}

async function reserve() {
  sending.value = true
  error.value = ''
  try {
    await request<Reservation>('/reservations', { method: 'POST', body: { donationId: id } })
    message.value = 'Listo, la donación es tuya. Coordina la recogida con el donador.'
    await load()
  } catch (e) {
    if (e instanceof ApiError && e.status === 409 && donation.value) {
      // 409 = otra organización la reservó primero: ya no se puede reservar
      error.value = 'Llegaste tarde: otra organización ya reservó esta donación.'
      donation.value = { ...donation.value, status: 'RESERVED' }
    } else {
      error.value = (e as Error).message
    }
  } finally {
    sending.value = false
  }
}

async function release() {
  if (!confirm('¿Liberar la reserva? La donación vuelve a quedar disponible para otros.')) return
  sending.value = true
  try {
    await request(`/reservations/${id}`, { method: 'DELETE' })
    message.value = 'Reserva liberada.'
    await load()
  } catch (e) {
    error.value = (e as Error).message
  } finally {
    sending.value = false
  }
}

onMounted(async () => {
  await auth.whenReady()
  await loadCatalog().catch(() => null)
  await load()
})
</script>
