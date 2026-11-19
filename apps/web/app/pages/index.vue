<template>
  <div class="space-y-6">
    <section class="rounded-box bg-primary p-6 text-primary-content sm:p-8">
      <p class="text-sm font-semibold uppercase tracking-widest opacity-80">Rescate de alimentos · Guanajuato</p>
      <h1 class="mt-2 max-w-2xl text-3xl font-black leading-tight sm:text-4xl">
        Comida que no se vendió, en manos de quien la necesita
      </h1>
      <p class="mt-3 max-w-2xl opacity-90">
        Las donaciones se ordenan por prioridad: primero lo que vence pronto, está cerca y es más alimento.
      </p>
      <div v-if="!auth.user && auth.ready" class="mt-5 flex flex-wrap gap-2">
        <NuxtLink to="/register" class="btn btn-secondary">Crear cuenta</NuxtLink>
        <NuxtLink to="/login" class="btn btn-ghost border-primary-content/40">Ya tengo cuenta</NuxtLink>
      </div>
    </section>

    <section class="panel grid gap-3 !p-4 sm:grid-cols-2 lg:grid-cols-5">
      <input v-model="filters.q" class="input w-full lg:col-span-2" placeholder="Buscar: pan, fruta, cafetería..." />
      <select v-model="filters.category" class="select w-full">
        <option value="">Todas las categorías</option>
        <option v-for="item in catalog?.categories" :key="item.value" :value="item.value">{{ item.label }}</option>
      </select>
      <select v-model="filters.zone" class="select w-full">
        <option value="">Todas las zonas</option>
        <option v-for="item in catalog?.zones" :key="item.value" :value="item.value">{{ item.label }}</option>
      </select>
      <select v-model="filters.validity" class="select w-full">
        <option value="valid">Vigentes</option>
        <option value="24h">Vencen en 24 h</option>
        <option value="all">Incluir vencidas</option>
      </select>
      <label v-if="auth.role !== 'RECEIVER'" class="flex items-center gap-2 text-sm sm:col-span-2 lg:col-span-5">
        <span class="opacity-70">Calcular cercanía desde</span>
        <select v-model="filters.from" class="select select-sm w-auto">
          <option value="">sin zona</option>
          <option v-for="item in catalog?.zones" :key="item.value" :value="item.value">{{ item.label }}</option>
        </select>
      </label>
    </section>

    <div v-if="error" role="alert" class="alert alert-error">{{ error }}</div>

    <div class="flex items-baseline justify-between">
      <h2 class="text-lg font-bold">Donaciones disponibles</h2>
      <span class="text-sm opacity-60">{{ donations.length }} resultado(s)</span>
    </div>

    <div v-if="loading" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="n in 3" :key="n" class="skeleton h-56 w-full" />
    </div>
    <div v-else-if="!donations.length" class="panel text-center">
      <p class="font-semibold">No hay donaciones con esos filtros</p>
      <p class="text-sm opacity-70">Prueba con otra zona o vuelve más tarde.</p>
    </div>
    <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <DonationCard v-for="donation in donations" :key="donation.id" :donation="donation" />
    </div>
  </div>
</template>

<script setup lang="ts">
import type { Donation } from '~/types'
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const { request } = useApi()
const { catalog, load: loadCatalog } = useCatalog()
const donations = ref<Donation[]>([])
const loading = ref(true)
const error = ref('')
const filters = reactive({ q: '', category: '', zone: '', validity: 'valid', from: '' })

async function load() {
  loading.value = true
  error.value = ''
  const params = new URLSearchParams()
  for (const [key, value] of Object.entries(filters)) {
    if (value) params.set(key, value)
  }
  try {
    donations.value = await request<Donation[]>(`/donations?${params}`)
  } catch (e) {
    error.value = (e as Error).message
  } finally {
    loading.value = false
  }
}

// Espera a saber si hay sesión para que la prioridad use la zona del receptor.
onMounted(async () => {
  await auth.whenReady()
  await loadCatalog().catch(() => null)
  await load()
})

let timer: ReturnType<typeof setTimeout> | undefined
watch(filters, () => {
  clearTimeout(timer)
  timer = setTimeout(load, 300)
})
watch(() => auth.role, load)
</script>
