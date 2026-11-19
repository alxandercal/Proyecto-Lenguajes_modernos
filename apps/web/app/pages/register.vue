<template>
  <div class="mx-auto max-w-xl">
    <div class="panel">
      <h1 class="page-title">Crear cuenta</h1>
      <p class="mt-1 text-sm opacity-70">Elige si tu organización dona o recibe alimento.</p>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <div class="grid grid-cols-2 gap-3">
          <button
            v-for="option in roles"
            :key="option.value"
            type="button"
            class="rounded-box border-2 p-4 text-left transition"
            :class="form.role === option.value ? 'border-primary bg-primary/10' : 'border-base-300'"
            @click="form.role = option.value"
          >
            <span class="block font-bold">{{ option.title }}</span>
            <span class="block text-xs opacity-70">{{ option.text }}</span>
          </button>
        </div>

        <label class="block">
          <span class="text-sm font-medium">Tu nombre</span>
          <input v-model="form.name" class="input mt-1 w-full" required minlength="2" maxlength="80" />
        </label>

        <label class="block">
          <span class="text-sm font-medium">{{ orgLabel }}</span>
          <input
            v-model="form.organizationName"
            class="input mt-1 w-full"
            required
            minlength="2"
            maxlength="100"
          />
        </label>

        <label class="block">
          <span class="text-sm font-medium">Zona</span>
          <select v-model="form.zone" class="select mt-1 w-full" required>
            <option value="" disabled>Elige tu zona</option>
            <option v-for="zone in catalog?.zones" :key="zone.value" :value="zone.value">
              {{ zone.label }}
            </option>
          </select>
        </label>

        <div class="grid gap-4 sm:grid-cols-2">
          <label class="block">
            <span class="text-sm font-medium">Correo</span>
            <input v-model="form.email" type="email" class="input mt-1 w-full" required />
          </label>
          <label class="block">
            <span class="text-sm font-medium">Contraseña</span>
            <input v-model="form.password" type="password" class="input mt-1 w-full" minlength="6" required />
          </label>
        </div>

        <div v-if="error" role="alert" class="alert alert-error text-sm">{{ error }}</div>

        <button class="btn btn-primary w-full" :disabled="auth.busy">
          {{ auth.busy ? 'Creando cuenta...' : 'Crear cuenta' }}
        </button>

        <p class="text-center text-sm">
          ¿Ya tienes cuenta? <NuxtLink to="/login" class="link link-primary">Inicia sesión</NuxtLink>
        </p>
      </form>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore, type RegisterInput } from '~/stores/auth'

const auth = useAuthStore()
const { catalog, load } = useCatalog()
const error = ref('')

const form = reactive<RegisterInput>({
  email: '',
  password: '',
  name: '',
  role: 'DONOR',
  organizationName: '',
  zone: ''
})

const roles = [
  { value: 'DONOR' as const, title: 'Dono alimento', text: 'Restaurante, cafetería o comercio' },
  { value: 'RECEIVER' as const, title: 'Recibo alimento', text: 'Banco de alimentos, comedor o albergue' }
]

const orgLabel = computed(() => form.role === 'DONOR' ? 'Nombre del negocio' : 'Nombre de la organización')

onMounted(() => load().catch((e: Error) => { error.value = e.message }))

async function submit() {
  error.value = ''
  try {
    await auth.register({ ...form })
    await navigateTo(auth.homePath())
  } catch (e) {
    error.value = (e as Error).message
  }
}
</script>