# Cotizaciones CRUD + Importación Firebase

Módulo para manejar cotizaciones migradas desde Firebase y creadas desde el frontend Quasar.

## Campos (tabla `cotizaciones`)

### Identificadores
- `id` - Primary Key
- `firebase_id` (nullable, con índice; se permiten repetidos)

### Relación con Usuario
- `usuario_id` → `users.id` (nullable, on delete set null)
- `usuario_data` (JSON): snapshot del objeto `usuario` original de Firebase

### Campos Principales
- `compania`
- `contacto`
- `telefono`
- `direccion`
- `ciudad`
- `terminos`
- `pago`
- `folio_servicio`
- `folio`
- `Nfolio`
- `area`
- `trabajo`
- `moneda`
- `tiempo`

### Estructuras JSON / Fecha
- `conceptos` (JSON array)
- `fechas` (JSON)
- `salida` (timestamp)

### Estados UI
- `boton_deshabilitado` (boolean)
- `procesando_accion` (boolean)

### Timestamps automáticos
- `created_at`, `updated_at`

---

## Endpoints (admin)

Base: `/api/admin/cotizaciones`

1. `GET /api/admin/cotizaciones`
- Lista cotizaciones (paginado por default: 50).
- Filtros disponibles: `folio`, `compania`, `usuario_id`.
- Si envías `?paginate=false`, devuelve arreglo simple.

2. `GET /api/admin/cotizaciones-list`
- Devuelve todas las cotizaciones sin paginación.

3. `POST /api/admin/cotizaciones`
- Crea una cotización.

Body ejemplo:
```json
{
  "firebase_id": "01AS1fbnffXh52Yhsw3E",
  "usuario_id": 2,
  "usuario_data": {
    "id": "xbwpj3OHRmEFTMxE2zcd",
    "nombre": "Cristofer Ponce",
    "email": "jacinto.jimenez@serviram.com.mx",
    "puesto": "Tecnico",
    "Cfolio": 7,
    "Dfolio": 26
  },
  "compania": "Alboa PLAZA LANDMARK GUADALAJARA",
  "contacto": "GERMAN FERNANDEZ",
  "telefono": "(442) 385 - 6550",
  "direccion": "AV PATRIA NO. 188",
  "ciudad": "ZAPOPAN, JALISCO",
  "terminos": "pago por adelantado",
  "pago": "pago por adelantado",
  "folio_servicio": "",
  "folio": "xbw0008",
  "Nfolio": "0008",
  "area": "",
  "trabajo": "",
  "moneda": "MXN",
  "tiempo": "",
  "fechas": {
    "dateF": "2025-11-11",
    "mes": 11,
    "dateR": "2025-11-11",
    "semana": 46
  },
  "conceptos": [
    {
      "Descripcion": "servicio de mantenimiento",
      "Cantidad": 1,
      "PrecioUnitario": 3500
    }
  ],
  "salida": "2025-11-11T00:00:00Z"
}
```

4. `GET /api/admin/cotizaciones/{cotizacion}`
- Obtiene cotización por ID.

5. `PUT /api/admin/cotizaciones/{cotizacion}`
- Actualiza cotización.

6. `DELETE /api/admin/cotizaciones/{cotizacion}`
- Elimina cotización.

---

## Importación desde Firebase

Comando:

```bash
php artisan cotizaciones:import-firebase storage/app/imports/Cotizaciones.json --dry-run
php artisan cotizaciones:import-firebase storage/app/imports/Cotizaciones.json
```

### Formato esperado del JSON

```json
[
  {
    "id": "01AS1fbnffXh52Yhsw3E",
    "data": {
      "folio": "xbw0008",
      "Nfolio": "0008",
      "compania": "Alboa PLAZA LANDMARK GUADALAJARA",
      "contacto": "GERMAN FERNANDEZ",
      "usuario": {
        "id": "xbwpj3OHRmEFTMxE2zcd",
        "nombre": "Cristofer Ponce",
        "email": "jacinto.jimenez@serviram.com.mx"
      },
      "fechas": {
        "dateF": "2025-11-11",
        "mes": 11,
        "dateR": "2025-11-11",
        "semana": 46
      },
      "salida": {
        "type": "firestore/timestamp/1.0",
        "seconds": 1762819200,
        "nanoseconds": 0
      },
      "conceptos": []
    }
  }
]
```

### Lógica de relación de usuario

1. Toma `data.usuario.id` (Firebase ID del usuario).
2. Busca en tabla `users` por `users.firebase_id = data.usuario.id`.
3. Si encuentra usuario, guarda su `id` en `usuario_id`.
4. Si no encuentra, deja `usuario_id = null`.
5. Siempre guarda también el objeto completo en `usuario_data`.

Esto te permite conservar historial aunque cambie o no exista el usuario relacionado.

---

## Flujo recomendado para migrar

1. Asegurar usuarios importados primero:
```bash
php artisan users:import-firebase storage/app/imports/users.json
```

2. Correr migración:
```bash
php artisan migrate
```

3. Probar cotizaciones sin grabar:
```bash
php artisan cotizaciones:import-firebase storage/app/imports/Cotizaciones.json --dry-run
```

4. Ejecutar importación real:
```bash
php artisan cotizaciones:import-firebase storage/app/imports/Cotizaciones.json
```

5. Validar total:
```bash
php artisan tinker --execute="echo App\\Models\\Cotizacion::count();"
```

---

## Uso rápido en frontend (axios)

```js
// listar paginado
const { data } = await api.get('/admin/cotizaciones')

// listar todo
const { data: all } = await api.get('/admin/cotizaciones-list')

// crear
await api.post('/admin/cotizaciones', payload)

// actualizar
await api.put(`/admin/cotizaciones/${id}`, payload)

// eliminar
await api.delete(`/admin/cotizaciones/${id}`)
```
