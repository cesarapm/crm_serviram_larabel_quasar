# Buzón de Servicios

Módulo de preparación de requerimientos de servicio. Permite capturar la información necesaria para que un operador la revise y la agende manualmente en el calendario.

## Flujo de estatus

```
nuevo  →  en_revision  →  agendado  →  completado
```

- **nuevo**: Entrada recién creada (desde Quasar o plataforma externa).
- **en_revision**: Un operador la está revisando.
- **agendado**: Se ejecutó la acción `/agendar` — se creó automáticamente un registro en la tabla `agendas`.
- **completado**: Servicio finalizado.

> Un requerimiento en estado `agendado` **no puede revertirse** a `nuevo` o `en_revision`.  
> Un requerimiento en estado `agendado` **no puede eliminarse** sin cambiar el estatus antes.

---

## Campos (tabla `buzon_servicios`)

| Campo | Tipo | Notas |
|---|---|---|
| `id` | integer | PK |
| `firebase_id` | string nullable | ID del registro en plataforma externa |
| `cliente_id` | integer nullable | FK → `clientes.id` |
| `orden_id` | integer nullable | FK → `ordenes.id` |
| `agenda_id` | integer nullable | FK → `agendas.id` — se llena al ejecutar `/agendar` |
| `tecnico_id` | integer nullable | FK → `users.id` |
| `creado_por_id` | integer nullable | FK → `users.id` — usuario autenticado que creó el registro |
| `cliente_data` | JSON | Snapshot del cliente (compania, contacto, telefono, email…) |
| `servicio_descripcion` | text nullable | Descripción del servicio requerido |
| `tipo_equipo` | string nullable | |
| `equipo_data` | JSON nullable | Datos del equipo específico |
| `fecha_solicitada` | string nullable | Fecha preferida (string libre, ej. `"2026-05-10"`) |
| `fechas` | JSON nullable | Rango o listado de fechas preferidas |
| `prioridad` | enum | `alta` / `media` / `baja` — default: `media` |
| `tecnico_data` | JSON nullable | Snapshot del técnico |
| `notas` | text nullable | |
| `estatus` | enum | `nuevo` / `en_revision` / `agendado` / `completado` — default: `nuevo` |
| `created_at` | timestamp | |
| `updated_at` | timestamp | |

---

## Endpoints

Base: `/api/buzon`  
Autenticación: `Bearer {token}` (Sanctum) en todos los endpoints.  
Módulo de permiso requerido: `buzon` (el rol `admin` tiene acceso automático).

---

### GET `/api/buzon`

Lista paginada (50 por página) ordenada por prioridad (alta → media → baja) y luego por `created_at` desc.

**Query params opcionales**

| Param | Descripción |
|---|---|
| `estatus` | `nuevo` \| `en_revision` \| `agendado` \| `completado` |
| `prioridad` | `alta` \| `media` \| `baja` |
| `tipo_equipo` | búsqueda parcial |
| `fecha_desde` | `YYYY-MM-DD` — filtra `fecha_solicitada >= fecha_desde` |
| `fecha_hasta` | `YYYY-MM-DD` — filtra `fecha_solicitada <= fecha_hasta` |
| `buscar` | búsqueda libre sobre `servicio_descripcion`, `tipo_equipo`, `cliente_data->compania`, `cliente_data->contacto` |
| `paginate=false` | devuelve todos los registros sin paginación |

**Response 200**
```json
{
  "current_page": 1,
  "data": [ /* array de BuzonServicio */ ],
  "per_page": 50,
  "total": 12
}
```

Cada ítem incluye los objetos relacionados: `cliente`, `orden`, `agenda`, `tecnico`, `creado_por`.

---

### POST `/api/buzon`

Crea una nueva entrada en el buzón.

**Body JSON**
```json
{
  "firebase_id": "abc123",
  "cliente_id": 5,
  "cliente_data": {
    "firebase_id": "xyz",
    "compania": "Empresa SA",
    "contacto": "Juan Pérez",
    "telefono": "555-1234",
    "email": "juan@empresa.com"
  },
  "orden_id": 12,
  "servicio_descripcion": "Mantenimiento preventivo de chillers",
  "tipo_equipo": "Chiller",
  "equipo_data": { "modelo": "Carrier 30XA", "serie": "AB123" },
  "fecha_solicitada": "2026-05-10",
  "fechas": ["2026-05-10", "2026-05-11"],
  "prioridad": "alta",
  "tecnico_id": 3,
  "tecnico_data": { "firebase_id": "tec01", "name": "Pedro", "email": "pedro@srv.com" },
  "notas": "El cliente solicita visita en la mañana"
}
```

> Si no envías `cliente_id` pero sí `cliente_data.firebase_id`, el sistema lo resuelve automáticamente buscando en la tabla `clientes`.  
> Si no envías `tecnico_id` pero sí `tecnico_data.firebase_id` (o `tecnico_data.email`), también se resuelve automáticamente.  
> `creado_por_id` se asigna automáticamente desde el usuario autenticado — no es necesario enviarlo.

**Response 201** — objeto BuzonServicio completo con relaciones.

---

### GET `/api/buzon/{id}`

Muestra un registro con todas las relaciones cargadas: `cliente`, `orden`, `agenda`, `tecnico`, `creado_por`.

**Response 200** — objeto BuzonServicio.

---

### PUT `/api/buzon/{id}`

Actualiza los campos del buzón. Todos los campos son opcionales (`sometimes`).  
Aplica las mismas resoluciones automáticas de `cliente_id` y `tecnico_id` que el `store`.

**Response 200** — objeto BuzonServicio actualizado.

---

### DELETE `/api/buzon/{id}`

Elimina el registro.

> **Error 422** si el estatus es `agendado` — debes cambiar el estatus antes de eliminar.

**Response 200**
```json
{ "status": "deleted" }
```

---

### POST `/api/buzon/{id}/agendar`

**Acción principal del flujo.** Crea automáticamente un registro en la tabla `agendas` con los datos del buzón y actualiza el estatus a `agendado`.

> **Error 422** si ya está `agendado` o si está `completado`.

**Body JSON** (todos opcionales — si no se envían, el sistema usa los datos del buzón)

| Campo | Descripción |
|---|---|
| `start` | Fecha/hora para la agenda (string). Si no se envía, usa `fecha_solicitada` del buzón |
| `start_raw` | String raw de fecha a conservar |
| `all_day` | boolean (default: false) |
| `text_color` | string color (ej. `"blue"`) |
| `title` | Título del evento en agenda. Si no se envía, se genera automáticamente como: `{compania} - {tipo_equipo} - {servicio_descripcion}` |
| `block` | boolean (default: false) |

**Datos que se copian automáticamente del buzón al evento de agenda**

- `orden_id` y `id_orden_firebase` (desde la orden vinculada)
- `equipo_data`
- `fecha_solicitada` → campo `fecha` de la agenda

**Response 200**
```json
{
  "buzon": { /* BuzonServicio actualizado con estatus: "agendado" y agenda_id lleno */ },
  "agenda": { /* Registro Agenda recién creado */ }
}
```

---

### PATCH `/api/buzon/{id}/estatus`

Cambia únicamente el estatus del buzón.

> **Error 422** si intentas revertir de `agendado` a `nuevo` o `en_revision`.

**Body JSON**
```json
{ "estatus": "en_revision" }
```

**Valores válidos:** `nuevo` | `en_revision` | `agendado` | `completado`

**Response 200** — objeto BuzonServicio actualizado.

---

### GET `/api/buzon/alertas`

Devuelve todos los registros con estatus `nuevo` o `en_revision`, ordenados por prioridad (alta primero) y fecha de creación.

> Para usuarios no-admin: solo muestra registros donde el usuario autenticado es el `tecnico_id` o el `creado_por_id`.  
> Para el rol `admin`: devuelve todos.

**Response 200**
```json
[
  {
    "id": 1,
    "estatus": "nuevo",
    "prioridad": "alta",
    "servicio_descripcion": "...",
    "notificacion": {
      "prioridad": "alta",
      "color": "red",
      "nivel": "alta"
    }
  }
]
```

**Colores por prioridad**

| Prioridad | Color |
|---|---|
| `alta` | `red` |
| `media` | `yellow` |
| `baja` | `green` |

---

## Resoluciones automáticas

El API resuelve IDs automáticamente para facilitar la integración con Firebase/plataforma externa:

| Si envías | El sistema busca |
|---|---|
| `cliente_data.firebase_id` | `clientes.firebase_id` → asigna `cliente_id` |
| `tecnico_data.firebase_id` | `users.firebase_id` → asigna `tecnico_id` |
| `tecnico_data.email` | `users.email` → asigna `tecnico_id` (si no hay `firebase_id`) |
| `creado_por_id` | Se asigna **automáticamente** desde el usuario autenticado |

---

## Permiso de módulo

El módulo se llama `buzon`. El rol `admin` tiene acceso total automáticamente.  
Para otros roles, se asigna desde la tabla `modulo_permisos` del usuario como el resto de módulos del sistema.
