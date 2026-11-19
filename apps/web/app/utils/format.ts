// Formatos para mostrar fechas, kilos y tiempo restante. Nuxt los importa solos.
const dateFormat = new Intl.DateTimeFormat('es-MX', {
  day: 'numeric',
  month: 'short',
  hour: '2-digit',
  minute: '2-digit'
})

export function formatDate(iso: string): string {
  return iso ? dateFormat.format(new Date(iso)) : ''
}

export function formatKg(kg: number): string {
  return `${Number(kg).toLocaleString('es-MX', { maximumFractionDigits: 1 })} kg`
}

export function formatQuantity(quantity: number, unit: string): string {
  const names: Record<string, string> = { KG: 'kg', L: 'L', PIEZA: 'piezas', PORCION: 'porciones' }
  return `${Number(quantity).toLocaleString('es-MX')} ${names[unit] ?? unit}`
}

// "Vence en 5 h", "Vence en 2 días", "Venció"
export function timeLeft(iso: string, now = Date.now()): string {
  const hours = (new Date(iso).getTime() - now) / 3_600_000
  if (hours <= 0) return 'Venció'
  if (hours < 1) return 'Vence en menos de 1 h'
  if (hours < 48) return `Vence en ${Math.floor(hours)} h`
  return `Vence en ${Math.floor(hours / 24)} días`
}

export function isUrgent(iso: string, now = Date.now()): boolean {
  const hours = (new Date(iso).getTime() - now) / 3_600_000
  return hours > 0 && hours <= 12
}

// De "2026-11-20T18:00:00Z" al formato que pide <input type="datetime-local">
export function toLocalInput(iso: string): string {
  const date = new Date(iso)
  const offset = date.getTimezoneOffset() * 60_000
  return new Date(date.getTime() - offset).toISOString().slice(0, 16)
}