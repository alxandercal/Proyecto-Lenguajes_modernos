<template>
  <header class="sticky top-0 z-20 border-b border-base-300 bg-base-100/95 backdrop-blur">
    <nav class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3 sm:px-6">
      <NuxtLink to="/" class="flex items-center gap-2 text-lg font-black tracking-tight">
        <span class="grid size-8 place-items-center rounded-lg bg-primary text-primary-content">♻</span>
        <span class="whitespace-nowrap">ReFood <span class="hidden text-primary sm:inline">Connect</span></span>
      </NuxtLink>

      <div class="flex flex-wrap items-center justify-end gap-1 text-sm">
        <NuxtLink to="/" class="btn btn-ghost btn-sm">Donaciones</NuxtLink>

        <template v-if="auth.role === 'DONOR'">
          <NuxtLink to="/donor" class="btn btn-ghost btn-sm">Mi panel</NuxtLink>
          <NuxtLink to="/donations/new" class="btn btn-primary btn-sm">+ Publicar</NuxtLink>
        </template>

        <template v-if="auth.user">
          <span class="hidden px-2 text-xs opacity-70 md:inline">
            {{ auth.profile?.organizationName || auth.user.email }}
          </span>
          <button class="btn btn-outline btn-sm" @click="auth.logout()">Salir</button>
        </template>
        <template v-else-if="auth.ready">
          <NuxtLink to="/login" class="btn btn-ghost btn-sm">Ingresar</NuxtLink>
          <NuxtLink to="/register" class="btn btn-primary btn-sm">Crear cuenta</NuxtLink>
        </template>
      </div>
    </nav>
  </header>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
</script>