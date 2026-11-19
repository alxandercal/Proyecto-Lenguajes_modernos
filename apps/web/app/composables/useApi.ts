import { getIdToken } from 'firebase/auth'
import type { ApiResponse } from '~/types'

type Method = 'GET' | 'POST' | 'PATCH' | 'DELETE'

// Cliente de la API: agrega el token de Firebase y convierte los errores en mensajes claros.
export function useApi() {
  const apiBase = useRuntimeConfig().public.apiBase.replace(/\/$/, '')

  async function request<T>(path: string, options: { method?: Method; body?: object } = {}): Promise<T> {
    const auth = useNuxtApp().$firebaseAuth
    const headers: Record<string, string> = {}

    if (auth?.currentUser) {
      headers.Authorization = `Bearer ${await getIdToken(auth.currentUser)}`
    }

    try {
      const response = await $fetch<ApiResponse<T>>(`${apiBase}/api/v1${path}`, {
        method: options.method ?? 'GET',
        body: options.body,
        headers
      })
      return response.data
    } catch (error: unknown) {
      const failure = error as { data?: { error?: string }; statusCode?: number }
      if (failure.data?.error) {
        throw new ApiError(failure.data.error, failure.statusCode ?? 0)
      }
      throw new ApiError('No hay conexión con la API. ¿Está encendida?', 0)
    }
  }

  return { request }
}

// Error con el código HTTP, para reaccionar distinto a un 409 o a un 403.
export class ApiError extends Error {
  constructor(message: string, public status: number) {
    super(message)
  }
}