# ReFood Connect

Plataforma web para que la comida que restaurantes, cafeterías y comercios no vendieron llegue a bancos de alimentos, comedores y albergues. Proyecto 01 de Lenguajes Modernos de Programación, Universidad de Guanajuato.

| | |
| --- | --- |
| Frontend publicado | https://PENDIENTE.netlify.app |
| API publicada | https://PENDIENTE.onrender.com/api/v1/health |
| Repositorio | https://github.com/alxandercal/Proyecto-Lenguajes_modernos |

## Problema

Muchos negocios tiran comida en buen estado porque no tienen una forma rápida de avisar a quien la necesita. Las organizaciones se enteran tarde o llegan dos por la misma comida. Nadie lleva la cuenta de cuánto alimento se salvó.

## Objetivo

Coordinar a donadores y organizaciones receptoras con un flujo digital que se pueda seguir paso a paso. El donador publica, una sola organización reserva, el donador confirma la entrega con evidencia y el sistema suma los kilos recuperados.

## Alcance del MVP

- Registro e inicio de sesión con dos roles públicos (donador y receptor) y un administrador.
- Publicar, consultar, editar, cancelar y borrar donaciones.
- Buscar y filtrar por texto, categoría, zona y vigencia.
- Reserva única. Una segunda reserva de la misma donación recibe el error 409.
- Estados Publicada, Reservada, Entregada y Cancelada con historial de cada movimiento.
- Confirmación de entrega con evidencia (quién recibió, nota y enlace opcional a una foto).
- Indicadores de entregas y kilos recuperados por donador, por receptor y globales.
- Prioridad de recuperación de 0 a 100 que explica su puntaje (componente innovador).

Queda fuera del alcance subir fotos a Firebase Storage (pide el plan de pago), mapas y notificaciones.

## Roles

| Rol | Qué puede hacer |
| --- | --- |
| Donador `DONOR` | Publicar, editar, cancelar y borrar sus donaciones. Confirmar entregas. Ver sus kilos recuperados. |
| Receptor `RECEIVER` | Ver y filtrar donaciones, reservar, liberar su reserva y ver lo que ha recibido. |
| Administrador `ADMIN` | Ver los indicadores de toda la plataforma. Se crea a mano en la consola de Firebase. |

## Integrantes

| Integrante | Puesto | Área |
| --- | --- | --- |
| Sebastián Adair López Gutiérrez | Back 1 | Endpoints de la API, flujo de estados, repositorio, tags y publicación de la API |
| Raúl Santiago Leyva Carral | Back 2 | Firestore y reglas, dominio, prioridad, indicadores, pruebas y datos de demostración |
| Hugo Alejandro Villaseñor Calderón | Front 1 | Contrato del front, registro e inicio de sesión, pantallas del donador, entrega y Netlify |
| Gustavo Hernández Licea | Front 2 | Listado con filtros, prioridad, detalle, pantallas del receptor e indicadores globales |

## Tecnología

| Parte | Herramienta |
| --- | --- |
| Frontend | Nuxt 4, Vue 3, TypeScript, Pinia, Tailwind CSS 4 y daisyUI 5 |
| Backend | PHP 8.2 o superior sin frameworks, API REST |
| Datos | Firebase Cloud Firestore por su API REST con cURL |
| Autenticación | Firebase Authentication con correo y contraseña |
| Publicación | Netlify para el front y Render (Docker) para la API |

La arquitectura, el modelo de datos, los endpoints y el algoritmo están en la carpeta [docs](docs).

## Requisitos

- Node.js 22 o superior y npm 10 o superior.
- PHP 8.2 o superior con las extensiones `curl`, `json` y `mbstring`.
- Un proyecto de Firebase en plan Spark con Email/Password y Firestore en modo producción.

## Instalación

```bash
git clone https://github.com/alxandercal/Proyecto-Lenguajes_modernos.git refood-connect
cd refood-connect
npm install
npm run check:php
```

Copia los archivos de configuración y llénalos con los datos de tu app web de Firebase.

```powershell
Copy-Item apps/web/.env.example apps/web/.env
Copy-Item apps/api/config/local.php.example apps/api/config/local.php
```

En macOS o Linux usa `cp` en lugar de `Copy-Item`. Ninguno de los dos archivos se sube a GitHub.

| Variable | Archivo | Valor |
| --- | --- | --- |
| `NUXT_PUBLIC_API_BASE` | apps/web/.env | http://127.0.0.1:8000 |
| `NUXT_PUBLIC_FIREBASE_API_KEY` | apps/web/.env | apiKey de Firebase |
| `NUXT_PUBLIC_FIREBASE_AUTH_DOMAIN` | apps/web/.env | authDomain |
| `NUXT_PUBLIC_FIREBASE_PROJECT_ID` | apps/web/.env | projectId |
| `NUXT_PUBLIC_FIREBASE_APP_ID` | apps/web/.env | appId |
| `firebase_project_id` | apps/api/config/local.php | projectId |
| `firebase_api_key` | apps/api/config/local.php | apiKey |
| `allowed_origins` | apps/api/config/local.php | http://localhost:3000 y la URL de Netlify |

Publica el contenido de `firebase/firestore.rules` en Firestore Database, pestaña Rules.

## Ejecutar

```bash
npm run dev:api     # terminal 1, API en http://127.0.0.1:8000
npm run dev:web     # terminal 2, front en http://localhost:3000
npm run seed        # terminal 3, datos de demostración (con la API encendida)
```

## Cuentas de prueba (ficticias)

Todas usan la contraseña `demo1234` y las crea `npm run seed`.

| Correo | Rol | Organización |
| --- | --- | --- |
| espiga@demo.mx | Donador | Panadería La Espiga |
| mercado@demo.mx | Donador | Frutas Don Chuy |
| cafeteria@demo.mx | Donador | Cafetería Central León |
| banco@demo.mx | Receptor | Banco de Alimentos Centro |
| comedor@demo.mx | Receptor | Comedor San José |

Para el administrador, registra una cuenta como receptor y en la consola de Firestore cambia `users/{uid}.role` a `ADMIN`.

## Pruebas

```bash
npm run test:api    # 23 pruebas unitarias
npm run test:e2e    # prueba de punta a punta de 38 pasos con un Firebase falso, sin internet
npm run typecheck   # tipos de TypeScript del front
npm run build:web   # compila el front como en Netlify
```

## Modo sin internet

Sirve para ver la app completa sin un proyecto de Firebase. Usa un Firebase falso que no aplica las reglas.

```powershell
# Terminal 1
npm run fake:firebase

# Terminal 2
$env:FIREBASE_PROJECT_ID="demo-refood"; $env:FIREBASE_API_KEY="fake-key"
$env:FIRESTORE_EMULATOR_HOST="127.0.0.1:9199"; $env:FIREBASE_AUTH_EMULATOR_HOST="127.0.0.1:9199"
npm run dev:api
```

En `apps/web/.env` pon `NUXT_PUBLIC_FIREBASE_API_KEY=fake-key`, `NUXT_PUBLIC_FIREBASE_PROJECT_ID=demo-refood` y `NUXT_PUBLIC_FIREBASE_AUTH_EMULATOR_HOST=127.0.0.1:9199`, y corre `npm run dev:web`.

## Prueba final del proyecto

1. El donador publica 5 kg de alimento.
2. Un receptor la reserva.
3. Otro receptor intenta reservarla y recibe "otra organización ya reservó esta donación".
4. El donador confirma la entrega con evidencia.
5. El total recuperado del donador, del receptor y de la plataforma aumenta en 5 kg.

## Revisiones

| Revisión | Fecha | Tag | Evidencia |
| --- | --- | --- | --- |
| R1 | 19 nov 2026 | `revision-1-2026-11-19` | [R1.md](docs/revisiones/R1.md) |
| R2 | 26 nov 2026 | `revision-2-2026-11-26` | [R2.md](docs/revisiones/R2.md) |
| R3 | 3 dic 2026 | `revision-3-2026-12-03` | [R3.md](docs/revisiones/R3.md) |
