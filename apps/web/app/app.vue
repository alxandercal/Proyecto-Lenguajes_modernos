<template>
  <div class="flex min-h-screen flex-col">
    <AppHeader />
    <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-6 sm:px-6 sm:py-8">
      <NuxtPage />
    </main>
    <footer class="border-t border-base-300 py-5 text-center text-xs opacity-60">
      ReFood Connect · Lenguajes Modernos de Programación · Universidad de Guanajuato ·
      Proyecto académico con datos ficticios
    </footer>
  </div>
</template>

<script setup lang="ts">
import { useAuthStore } from '~/stores/auth'

const auth = useAuthStore()
onMounted(() => auth.initialize())
</script>