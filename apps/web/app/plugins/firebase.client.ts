import { initializeApp, getApps } from 'firebase/app'
import { connectAuthEmulator, getAuth, type Auth } from 'firebase/auth'

// Inicia Firebase Authentication en el navegador. Los datos vienen de apps/web/.env
export default defineNuxtPlugin(() => {
  const config = useRuntimeConfig().public
  let auth: Auth | null = null

  if (config.firebaseApiKey && !config.firebaseApiKey.startsWith('REEMPLAZAR')) {
    const app = getApps()[0] ?? initializeApp({
      apiKey: config.firebaseApiKey,
      authDomain: config.firebaseAuthDomain,
      projectId: config.firebaseProjectId,
      appId: config.firebaseAppId
    })
    auth = getAuth(app)
    if (config.firebaseAuthEmulatorHost) {
      connectAuthEmulator(auth, `http://${config.firebaseAuthEmulatorHost}`, { disableWarnings: true })
    }
  } else {
    console.warn('Falta configurar Firebase en apps/web/.env')
  }

  return { provide: { firebaseAuth: auth } }
})