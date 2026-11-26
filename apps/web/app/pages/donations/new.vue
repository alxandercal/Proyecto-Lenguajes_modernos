<template>
  <div class="mx-auto max-w-2xl space-y-4">
    <NuxtLink to="/donor" class="link link-primary text-sm">← Mi panel</NuxtLink>

    <RoleNotice
      v-if="auth.ready && auth.role !== 'DONOR'"
      message="Solo las cuentas de donador pueden publicar alimento."
    />

    <form v-else class="panel space-y-4" @submit.prevent="save">
      <h1 class="page-title">{{ editId ? 'Editar donación' : 'Publicar donación' }}</h1>

      <label class="block">
        <span class="text-sm font-medium">¿Qué donas?</span>
        <input
          v-model="form.title"
          class="input mt-1 w-full"
          placeholder="Ej. Pan dulce del día"
          required
          minlength="3"
          maxlength="80"
        />
      </label>

      <div class="grid gap-4 sm:grid-cols-3">
        <label class="block">
          <span class="text-sm font-medium">Categoría</span>
          <select v-model="form.category" class="select mt-1 w-full" required>
            <option v-for="item in catalog?.categories" :key="item.value" :value="item.value">
              {{ item.label }}
            </option>
          </select>
        </label>

        <label class="block">
          <span class="text-sm font-medium">Cantidad</span>
          <input
            v-model.number="form.quantity"
            type="number"
            min="0.1"
            max="5000"
            step="0.1"
            class="input mt-1 w-full"
            required
          />
        </label>

        <label class="block">
          <span class="text-sm font-medium">Unidad</span>
          <select v-model="form.unit" class="select mt-1 w-full">
            <option v-for="item in catalog?.units" :key="item.value" :value="item.value">{{ item.label }}</option>
          </select>
        </label>
      </div>

      <p class="-mt-2 text-xs opacity-70">Equivale a {{ formatKg(kgPreview) }} para los indicadores.</p>

      <div class="grid gap-4 sm:grid-cols-2">
        <label class="block">
          <span class="text-sm font-medium">Se puede consumir hasta</span>
          <input v-model="expiresLocal" type="datetime-local" class="input mt-1 w-full" :min="minDate" required />
        </label>

        <label class="block">
          <span class="text-sm font-medium">Zona</span>
          <select v-model="form.zone" class="select mt-1 w-full" required>
            <option v-for="item in catalog?.zones" :key="item.value" :value="item.value">{{ item.label }}</option>
          </select>
        </label>
      </div>

      <label class="block">
        <span class="text-sm font-medium">Punto de recogida</span>
        <input
          v-model="form.pickupAddress"
          class="input mt-1 w-full"
          placeholder="Calle, número y referencia"
          required
          minlength="5"
          maxlength="160"
        />
      </label>

      <label class="block">
        <span class="text-sm font-medium">Notas (opcional)</span>
        <textarea
          v-model="form.notes"
          class="textarea mt-1 w-full"
          maxlength="300"
          placeholder="Horario, si deben traer cajas, etc."
        />
      </label>

      <div v-if="error" role="alert" class="alert alert-error text-sm">{{ error }}</div>

      <button class="btn btn-primary w-full" :disabled="sending">
        {{ sending ? 'Guardando...' : editId ? 'Guardar cambios' : 'Publicar donación' }}
      </button>
    </form>
  </div>
</template>

<script setup lang="ts">
import type { Donation, DonationInput } from '~/types'
import { useAuthStore } from '~/stores/auth'

// Publicar una donación. Con ?edit=ID el mismo formulario sirve para editar.
const auth = useAuthStore()
const route = useRoute()
const { request } = useApi()
const { catalog, load } = useCatalog()

const editId = typeof route.query.edit === 'string' ? route.query.edit : ''

const form = reactive<DonationInput>({
  title: '',
  category: 'PREPARADOS',
  quantity: 1,
  unit: 'KG',
  expiresAt: '',
  pickupAddress: '',
  zone: '',
  notes: ''
})

const expiresLocal = ref('')
const error = ref('')
const sending = ref(false)
const minDate = toLocalInput(new Date().toISOString())

const kgPreview = computed(() => {
  const unit = catalog.value?.units.find(item => item.value === form.unit)
  return Math.round((form.quantity || 0) * (unit?.kg ?? 1) * 100) / 100
})

onMounted(async () => {
  await auth.whenReady()
  await load().catch((e: Error) => { error.value = e.message })
  form.zone = auth.profile?.zone ?? ''

  if (editId) {
    try {
      const current = await request<Donation>(`/donations/${editId}`)
      Object.assign(form, {
        title: current.title,
        category: current.category,
        quantity: current.quantity,
        unit: current.unit,
        pickupAddress: current.pickupAddress,
        zone: current.zone,
        notes: current.notes
      })
      expiresLocal.value = toLocalInput(current.expiresAt)
    } catch (e) {
      error.value = (e as Error).message
    }
  }
})

async function save() {
  sending.value = true
  error.value = ''
  try {
    form.expiresAt = new Date(expiresLocal.value).toISOString()
    const saved = editId
      ? await request<Donation>(`/donations/${editId}`, { method: 'PATCH', body: form })
      : await request<Donation>('/donations', { method: 'POST', body: form })
    await navigateTo(`/donations/${saved.id}`)
  } catch (e) {
    error.value = (e as Error).message
  } finally {
    sending.value = false
  }
}
</script>
