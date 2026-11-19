<template>
  <div class="space-y-5">
    <RoleNotice v-if="auth.ready && auth.role !== 'DONOR'" message="El panel es para cuentas de donador." />

    <template v-else>
      <div class="flex flex-wrap items-end justify-between gap-3">
        <div>
          <h1 class="page-title">Mi panel</h1>
          <p class="opacity-70">{{ auth.profile?.organizationName }}</p>
        </div>
        <NuxtLink to="/donations/new" class="btn btn-primary">+ Publicar donación</NuxtLink>
      </div>

      <div class="grid grid-cols-2 gap-3 md:grid-cols-4">
        <div class="panel !p-4">
          <p class="text-xs opacity-60">Donaciones</p>
          <p class="text-2xl font-black text-primary">{{ donations.length }}</p>
        </div>
        <div class="panel !p-4">
          <p class="text-xs opacity-60">Entregadas</p>
          <p class="text-2xl font-black">{{ count('DELIVERED') }}</p>
        </div>
        <div class="panel !p-4">
          <p class="text-xs opacity-60">Publicadas</p>
          <p class="text-2xl font-black">{{ count('PUBLISHED') }}</p>
        </div>
        <div class="panel !p-4">
          <p class="text-xs opacity-60">Por entregar</p>
          <p class="text-2xl font-black text-warning">{{ count('RESERVED') }}</p>
        </div>
      </div>

      <div v-if="error" role="alert" class="alert alert-error">{{ error }}</div>

      <section class="panel overflow-x-auto !p-0">
        <div v-if="loading" class="skeleton m-4 h-32" />
        <p v-else-if="!donations.length" class="p-8 text-center opacity-70">
          Todavía no publicas donaciones.
        </p>
        <table v-else class="table">
          <thead>
            <tr>
              <th>Donación</th><th>Cantidad</th><th>Vence</th><th>Estado</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in donations" :key="item.id">
              <td>
                <NuxtLink :to="`/donations/${item.id}`" class="font-semibold hover:text-primary">
                  {{ item.title }}
                </NuxtLink>
                <p v-if="item.reservedByName" class="text-xs opacity-60">Reservó: {{ item.reservedByName }}</p>
              </td>
              <td class="whitespace-nowrap">{{ formatQuantity(item.quantity, item.unit) }}</td>
              <td class="whitespace-nowrap text-sm">{{ formatDate(item.expiresAt) }}</td>
              <td><StatusBadge :status="item.status" /></td>
            </tr>
          </tbody>
        </table>
      </section>
    </template>
  </div>
</template>

<script setup lang="ts">
import type { Donation, DonationStatus } from '~/types'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const { request } = useApi()
const donations = ref<Donation[]>([])
const loading = ref(true)
const error = ref('')

const count = (status: DonationStatus) => donations.value.filter(item => item.status === status).length

async function load() {
  if (auth.role !== 'DONOR') return
  loading.value = true
  try {
    donations.value = await request<Donation[]>('/donor/donations')
  } catch (e) {
    error.value = (e as Error).message
  } finally {
    loading.value = false
  }
}

onMounted(async () => {
  await auth.whenReady()
  await load()
})
</script>