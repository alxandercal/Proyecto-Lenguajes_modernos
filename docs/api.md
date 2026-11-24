# API REST

Base local `http://127.0.0.1:8000/api/v1`. Las respuestas correctas traen `{"data": ...}` y los errores `{"error": "mensaje"}`. Las rutas con rol piden el encabezado `Authorization: Bearer <token de Firebase>`.

| Método | Ruta | Quién | Qué hace | Desde |
| --- | --- | --- | --- | --- |
| GET | `/health` | Cualquiera | Comprueba que la API responde | R1 |
| GET | `/catalog` | Cualquiera | Categorías, unidades, zonas y estados | R1 |
| POST | `/profiles` | Cuenta nueva | Crea el perfil como DONOR o RECEIVER | R1 |
| GET | `/me` | Con sesión | Perfil con rol | R1 |
| GET | `/donations` | Cualquiera | Publicadas, con filtros y prioridad | R1 |
| POST | `/donations` | DONOR | Publica una donación | R1 |
| GET | `/donor/donations` | DONOR | Sus donaciones en cualquier estado | R1 |
| GET | `/donations/{id}` | Según el estado | Detalle con historial y prioridad | R1 |
| PATCH | `/donations/{id}` | DONOR dueño | Edita, o cancela con `{"action":"cancel"}` | R2 |
| DELETE | `/donations/{id}` | DONOR dueño | Borra si nunca tuvo reserva | R2 |
| POST | `/reservations` | RECEIVER | Reserva. 409 si ya está apartada | R2 |
| GET | `/reservations/mine` | RECEIVER | Sus reservas | R2 |
| DELETE | `/reservations/{id}` | RECEIVER dueño | Libera la reserva | R2 |
| POST | `/pickups` | DONOR dueño | Confirma la entrega con evidencia | R3 |
| GET | `/pickups/{id}` | Donador, receptor o ADMIN | Consulta la evidencia | R3 |
| GET | `/metrics/mine` | Con sesión | Indicadores según el rol | R3 |
| GET | `/metrics` | ADMIN | Indicadores globales | R3 |

## Filtros de GET /donations

| Parámetro | Valores | Ejemplo |
| --- | --- | --- |
| `q` | texto libre en título, donador y notas | `?q=pan` |
| `category` | una categoría del catálogo | `?category=PANADERIA` |
| `zone` | una zona del catálogo | `?zone=LEON` |
| `validity` | `valid` (por defecto), `24h` o `all` | `?validity=24h` |
| `from` | zona para medir cercanía sin sesión | `?from=SILAO` |

## Ejemplos

Publicar una donación.

```http
POST /api/v1/donations
Authorization: Bearer eyJ...
Content-Type: application/json

{
  "title": "Pan dulce del día",
  "category": "PANADERIA",
  "quantity": 5,
  "unit": "KG",
  "expiresAt": "2026-11-20T18:00:00Z",
  "pickupAddress": "Av. Juárez 120, Centro",
  "zone": "GUANAJUATO",
  "notes": "Traer cajas"
}
```

Respuesta 201 con la donación guardada, su `id`, `status: "PUBLISHED"`, `quantityKg` y `history`.

Reservar.

```http
POST /api/v1/reservations
{"donationId": "a1b2c3d4e5f6a7b8c9d0"}
```

Respuesta 201 con la reserva. Si otra organización la reservó antes la respuesta es 409.

```json
{"error": "Esta donación ya fue reservada por otra organización."}
```

Confirmar la entrega.

```http
POST /api/v1/pickups
{"donationId": "a1b2c3d4e5f6a7b8c9d0", "receivedBy": "Laura Méndez",
 "note": "Entregado completo", "evidenceUrl": "https://photos.app.goo.gl/..."}
```

## Códigos de respuesta

| Código | Cuándo |
| --- | --- |
| 200 y 201 | Todo bien. 201 cuando se creó algo |
| 400 | JSON mal formado o identificador inválido |
| 401 | Falta el token o ya venció |
| 403 | El rol no puede hacer eso o la donación no es suya |
| 404 | No existe la ruta o el registro |
| 405 | La ruta existe pero con otro método |
| 409 | La donación ya fue reservada o el registro ya existe |
| 422 | Un dato no pasa la validación o el cambio de estado no se permite |
| 502 y 503 | Firebase no responde o falta configurar `local.php` |
