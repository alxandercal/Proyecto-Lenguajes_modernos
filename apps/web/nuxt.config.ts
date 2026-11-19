import tailwindcss from '@tailwindcss/vite'

// Configuración de Nuxt. ssr: false porque es una SPA que se publica en Netlify.
export default defineNuxtConfig({
  compatibilityDate: '2026-07-01',
  ssr: false,
  devtools: { enabled: false },
  modules: ['@pinia/nuxt'],
  css: ['~/assets/css/main.css'],
  vite: { plugins: [tailwindcss()] },
  app: {
    head: {
      htmlAttrs: { lang: 'es', 'data-theme': 'refood' },
      title: 'ReFood Connect · Rescate de alimentos',
      meta: [
        { name: 'viewport', content: 'width=device-width, initial-scale=1' },
        {
          name: 'description',
          content: 'Donadores publican alimento excedente y organizaciones receptoras lo rescatan.'
        }
      ]
    }
  },
  // Se llenan con las variables NUXT_PUBLIC_* de apps/web/.env
  runtimeConfig: {
    public: {
      apiBase: 'http://127.0.0.1:8000',
      firebaseApiKey: '',
      firebaseAuthDomain: '',
      firebaseProjectId: '',
      firebaseAppId: '',
      firebaseAuthEmulatorHost: ''
    }
  }
})  