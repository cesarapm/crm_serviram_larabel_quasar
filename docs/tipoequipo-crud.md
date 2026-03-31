# TipoEquipo CRUD + Importación Firebase

Módulo para manejar tipos de equipo con actividades de mantenimiento dinámicas.

## Campos (tabla `tipo_equipos`)

- `id`
- `firebase_id` (nullable, con índice; se permiten repetidos)
- `name` (index) - Nombre del tipo como "HORNO", "LICUADORA", etc.
- `mantenimiento` (JSON) - Array de actividades con name, orden, type
- `created_at`, `updated_at`

## Estructura de mantenimiento

Cada actividad en el array de mantenimiento tiene:
- `name` - Nombre de la actividad ("INSPECCION VISUAL", etc.)
- `orden` - Número de orden para UI
- `type` - Tipo de campo: "booleano" o "text"

## Endpoints (admin)

Base: `/api/admin/tipoequipos`

### 1. `GET /api/admin/tipoequipos`
Lista todos los tipos de equipo.

### 2. `POST /api/admin/tipoequipos`
Crea tipo de equipo.

Body ejemplo:
```json
{
  "firebase_id": "i8uFyJ8IGtb918zp38Ol",
  "name": "HORNO",
  "mantenimiento": [
    {
      "name": "INSPECCION VISUAL",
      "orden": 0,
      "type": "booleano"
    },
    {
      "name": "VOLTAJE",
      "orden": 14,
      "type": "text"
    }
  ]
}
```

### 3. `GET /api/admin/tipoequipos/{tipoEquipo}`
Ver un tipo por ID.

### 4. `PUT /api/admin/tipoequipos/{tipoEquipo}`
Actualizar tipo de equipo.

### 5. `DELETE /api/admin/tipoequipos/{tipoEquipo}`
Eliminar tipo de equipo.

## Importación desde Firebase

Comando:

```bash
php artisan tipoequipos:import-firebase storage/app/imports/tipoequipos.json --dry-run
php artisan tipoequipos:import-firebase storage/app/imports/tipoequipos.json
```

### Formato esperado del JSON

```json
[
  {
    "id": "i8uFyJ8IGtb918zp38Ol",
    "data": {
      "name": "HORNO",
      "mantenimiento": [
        {
          "name": "INSPECCION VISUAL",
          "orden": 0,
          "type": "booleano"
        },
        {
          "name": "VOLTAJE",
          "orden": 14,
          "type": "text"
        }
      ]
    }
  }
]
```

### Regla de importación

- Siempre hace `CREATE` por cada fila del JSON.
- No deduplica por `firebase_id`, `name` ni ningún otro campo.
- Si ejecutas el comando dos veces, se insertan duplicados (comportamiento intencional).
- El array `mantenimiento` se guarda completo en campo JSON.

## Uso en frontend (axios)

```js
// Listar tipos
const { data } = await api.get('/admin/tipoequipos')

// Crear tipo
const payload = {
  name: 'LICUADORA',
  mantenimiento: [
    { name: 'LIMPIEZA', orden: 0, type: 'booleano' },
    { name: 'VOLTAJE', orden: 1, type: 'text' }
  ]
}
await api.post('/admin/tipoequipos', payload)

// Actualizar tipo
await api.put(`/admin/tipoequipos/${id}`, payload)

// Eliminar tipo
await api.delete(`/admin/tipoequipos/${id}`)
```

## Integración con formulario Quasar

Para conectar con tu formulario existente:

```js
// Al cargar tipos desde backend
const { data: tipos } = await api.get('/admin/tipoequipos')

// Al guardar desde formulario
const tipoData = {
  name: data.tipo,
  mantenimiento: data.costos.map((costo, index) => ({
    ...costo,
    orden: index
  }))
}

await api.post('/admin/tipoequipos', tipoData)
```

## Validaciones

- `name`: requerido
- `mantenimiento`: array opcional
- `mantenimiento[].name`: string opcional
- `mantenimiento[].orden`: entero >= 0
- `mantenimiento[].type`: solo "booleano" o "text"