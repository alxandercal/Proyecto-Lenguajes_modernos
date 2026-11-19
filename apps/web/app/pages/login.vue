<template>
  <div class="mx-auto max-w-md">
    <div class="panel">
      <h1 class="page-title">Iniciar sesión</h1>
      <p class="mt-1 text-sm opacity-70">Entra para publicar o reservar donaciones.</p>

      <form class="mt-6 space-y-4" @submit.prevent="submit">
        <label class="block">
          <span class="text-sm font-medium">Correo</span>
          <input v-model="email" type="email" class="input mt-1 w-full" required autocomplete="email" />
        </label>

        <label class="block">
          <span class="text-sm font-medium">Contraseña</span>
          <input
            v-model="password"
            type="password"
            class="input mt-1 w-full"
            required
            autocomplete="current-password"
          />
        </label>

        <div v-if="error" role="alert" class="alert alert-error text-sm">{{ error }}</div>

        <button class="btn btn-primary w-full" :disabled="auth.busy">
          {{ auth.busy ? 'Entrando...' : 'Entrar' }}
        </button>
      </form>

      <p class="mt-5 text-center text-sm">
        ¿No tienes cuenta? <NuxtLink to="/register" class="link link-primary">Regístrate</NuxtLink>
      </p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
const email = ref('')
const password = ref('')
const error = ref('')

async function submit() {
  error.value = ''
  try {
    await auth.login(email.value, password.value)
    if (!auth.profile) {
      error.value = 'Tu cuenta no tiene perfil. Termina tu registro.'
      return
    }
    await navigateTo(auth.homePath())
  } catch (e) {
    error.value = (e as Error).message
  }
}
</script>