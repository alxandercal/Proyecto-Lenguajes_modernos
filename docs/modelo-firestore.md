# Modelo de datos en Firestore

Cinco colecciones. Las fechas se guardan como texto ISO 8601 en UTC, por ejemplo `2026-11-20T18:00:00Z`.

| Colección | Id del documento | Para qué |
| --- | --- | --- |
| `users` | uid de Firebase Auth | Perfil y rol de cada cuenta |
| `donations` | 20 caracteres que genera la API | Entidad principal con CRUD |
| `reservations` | el mismo id de la donación | Quién apartó cada donación |
| `pickups` | el mismo id de la donación | Evidencia de cada entrega |
| `impactMetrics` | `global` | Contador de entregas y kilos de toda la plataforma |

## users

| Campo | Tipo | Ejemplo |
| --- | --- | --- |
| uid | texto | `Xa81...` |
| email | texto | `espiga@demo.mx` |
| name | texto | `Marta Ruiz` |
| role | texto | `DONOR`, `RECEIVER` o `ADMIN` |
| organizationName | texto | `Panadería La Espiga` |
| zone | texto | `GUANAJUATO` |
| createdAt | texto | `2026-11-18T15:20:00Z` |

## donations

| Campo | Tipo | Ejemplo |
| --- | --- | --- |
| ownerId | texto | uid del donador |
| donorName | texto | `Panadería La Espiga` |
| title | texto | `Pan dulce del día` |
| category | texto | `PANADERIA` |
| quantity | número | `5` |
| unit | texto | `KG`, `L`, `PIEZA` o `PORCION` |
| quantityKg | número | `5` (piezas valen 0.25 kg y porciones 0.4 kg) |
| expiresAt | texto | `2026-11-20T18:00:00Z` |
| pickupAddress | texto | `Av. Juárez 120, Centro` |
| zone | texto | `GUANAJUATO` |
| notes | texto | `Traer cajas` |
| status | texto | `PUBLISHED`, `RESERVED`, `DELIVERED` o `CANCELLED` |
| reservedBy | texto | uid del receptor o vacío |
| reservedByName | texto | nombre de la organización o vacío |
| history | lista | movimientos `{at, action, byUid, byName, note}` |
| createdAt, updatedAt | texto | fechas |

Las acciones del historial son `PUBLISHED`, `EDITED`, `RESERVED`, `RELEASED`, `DELIVERED` y `CANCELLED`.

## reservations

donationId, donorId, donorName, receiverId, receiverName, donationTitle, quantity, unit, quantityKg, zone, pickupAddress, expiresAt, status (`ACTIVE`, `DELIVERED` o `CANCELLED`), createdAt y updatedAt.

## pickups

donationId, donorId, donorName, receiverId, receiverName, donationTitle, category, zone, quantityKg, receivedBy, note, evidenceUrl y deliveredAt.

## impactMetrics/global

deliveries (entero), kgRecovered (número), lastPickupId y updatedAt. Solo crece dentro del mismo commit que registra una entrega nueva.

## Reglas de seguridad

Están en `firebase/firestore.rules`.

| Colección | Leer | Escribir |
| --- | --- | --- |
| users | El propio usuario o ADMIN | Crear el propio perfil como DONOR o RECEIVER. No se edita ni se borra |
| donations | Publicadas cualquiera. Las demás el dueño, quien reservó o ADMIN | Crear solo DONOR. Cinco cambios permitidos (editar, cancelar, reservar, liberar y entregar) con su estado de origen y destino. Borrar solo publicadas |
| reservations | Receptor, donador o ADMIN | Crear solo RECEIVER en el mismo commit que reserva. El donador la cierra. El receptor la libera |
| pickups | Donador, receptor o ADMIN | Crear solo el donador dueño en el commit de entrega. No se edita ni se borra |
| impactMetrics | Cualquier usuario con sesión | Solo sube junto con una entrega nueva |
