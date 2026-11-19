<template>
  <div class="panel mx-auto max-w-lg text-center">
    <h2 class="text-lg font-bold">{{ title }}</h2>
    <p class="mt-1 text-sm opacity-70">{{ message }}</p>
    <div class="mt-4 flex justify-center gap-2">
      <NuxtLink v-if="!auth.user" to="/login" class="btn btn-primary btn-sm">Iniciar sesión</NuxtLink>
      <NuxtLink to="/" class="btn btn-ghost btn-sm">Ir al inicio</NuxtLink>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

// Aviso que se muestra cuando una página no corresponde al rol del usuario.
withDefaults(defineProps<{ title?: string; message: string }>(), {
  title: 'Esta sección no es para tu cuenta'
})
const auth = useAuthStore()
</script>