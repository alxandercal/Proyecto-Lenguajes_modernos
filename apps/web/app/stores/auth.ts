import { defineStore } from 'pinia'
import {
  createUserWithEmailAndPassword,
  onAuthStateChanged,
  signInWithEmailAndPassword,
  signOut,
  type User
} from 'firebase/auth'
import type { Profile, Role } from '~/types'

export interface RegisterInput {
  email: string
  password: string
  name: string
  role: Exclude<Role, 'ADMIN'>
  organizationName: string
  zone: string
}

// Mensajes de Firebase Authentication en español
function authMessage(error: unknown): string {
  const code = (error as { code?: string }).code ?? ''
  const messages: Record<string, string> = {
    'auth/email-already-in-use': 'Ese correo ya tiene cuenta. Inicia sesión.',
    'auth/invalid-email': 'El correo no es válido.',
    'auth/weak-password': 'La contraseña debe tener al menos 6 caracteres.',
    'auth/invalid-credential': 'Correo o contraseña incorrectos.',
    'auth/too-many-requests': 'Demasiados intentos. Espera un momento.',
    'auth/network-request-failed': 'No hay conexión con Firebase.'
  }
  return messages[code] ?? (error as Error).message ?? 'No se pudo completar la operación.'
}

// Sesión del usuario: cuenta de Firebase + perfil con rol que guarda la API.
export const useAuthStore = defineStore('auth', () => {
  const user = shallowRef<User | null>(null)
  const profile = ref<Profile | null>(null)
  const ready = ref(false)
  const busy = ref(false)
  let started = false
  let registering = false

  const role = computed<Role | null>(() => profile.value?.role ?? null)

  async function loadProfile() {
    if (!user.value) {
      profile.value = null
      return
    }
    try {
      profile.value = await useApi().request<Profile>('/me')
    } catch {
      profile.value = null // la cuenta existe pero todavía no tiene perfil
    }
  }

  // Se llama una vez en app.vue. Firebase avisa cuando cambia la sesión.
  function initialize() {
    if (started || !import.meta.client) return
    started = true
    const auth = useNuxtApp().$firebaseAuth
    if (!auth) {
      ready.value = true
      return
    }
    onAuthStateChanged(auth, async (current) => {
      user.value = current
      if (!registering) await loadProfile()
      ready.value = true
    })
  }

  // Espera a que Firebase diga si hay sesión (para las páginas que lo necesitan).
  async function whenReady() {
    if (ready.value) return
    await new Promise<void>((resolve) => {
      const stop = watch(ready, (value) => {
        if (value) {
          stop()
          resolve()
        }
      })
    })
  }

  async function register(input: RegisterInput) {
    const auth = useNuxtApp().$firebaseAuth
    if (!auth) throw new Error('Falta configurar Firebase en apps/web/.env')
    busy.value = true
    registering = true
    try {
      if (auth.currentUser?.email !== input.email.toLowerCase()) {
        const credential = await createUserWithEmailAndPassword(auth, input.email, input.password)
          .catch((e) => { throw new Error(authMessage(e)) })
        user.value = credential.user
      }
      profile.value = await useApi().request<Profile>('/profiles', {
        method: 'POST',
        body: {
          name: input.name,
          role: input.role,
          organizationName: input.organizationName,
          zone: input.zone
        }
      })
    } finally {
      registering = false
      busy.value = false
    }
  }

  async function login(email: string, password: string) {
    const auth = useNuxtApp().$firebaseAuth
    if (!auth) throw new Error('Falta configurar Firebase en apps/web/.env')
    busy.value = true
    try {
      const credential = await signInWithEmailAndPassword(auth, email, password)
        .catch((e) => { throw new Error(authMessage(e)) })
      user.value = credential.user
      await loadProfile()
    } finally {
      busy.value = false
    }
  }

  async function logout() {
    const auth = useNuxtApp().$firebaseAuth
    if (auth) await signOut(auth)
    user.value = null
    profile.value = null
    await navigateTo('/')
  }

  // A dónde mandar a cada rol después de entrar
  function homePath(): string {
    if (role.value === 'DONOR') return '/donor'
    if (role.value === 'ADMIN') return '/admin'
    return '/'
  }

  return {
    user, profile, role, ready, busy,
    initialize, whenReady, loadProfile, register, login, logout, homePath
  }
})