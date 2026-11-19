<template>
  <span class="badge badge-sm font-semibold" :class="style.css">{{ style.text }}</span>
</template>

<script setup lang="ts">
// Etiqueta de color para el estado de una donación o de una reserva.
const props = defineProps<{ status: string }>()

const styles: Record<string, { text: string; css: string }> = {
  PUBLISHED: { text: 'Publicada', css: 'badge-success' },
  RESERVED: { text: 'Reservada', css: 'badge-warning' },
  DELIVERED: { text: 'Entregada', css: 'badge-info' },
  CANCELLED: { text: 'Cancelada', css: 'badge-ghost' },
  ACTIVE: { text: 'Por recoger', css: 'badge-warning' }
}
const style = computed(() => styles[props.status] ?? { text: props.status, css: 'badge-ghost' })
</script>
