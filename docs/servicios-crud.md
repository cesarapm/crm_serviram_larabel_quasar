# Servicios CRUD + Importación Firebase

Módulo para manejar servicios de mantenimiento con relaciones a Usuario y Equipo.

## Campos (tabla `servicios`)

### Identificadores
- `id` - Primary Key
- `firebase_id` (nullable, con índice; se permiten repetidos)

### Relaciones (Foreign Keys Opcionales)
- `usuario_id` → `users.id` (nullable, on delete set null)  
- `equipo_id` → `products.id` (nullable, on delete set null)

### Cliente Embebido (JSON)
- `cliente_data` - JSON con: nombre, telefono, email, direccion, ciudad, responsable

### Campos Principales
- `status` - Estado del servicio (default: "Abierto") 
- `autorizacion` - Persona que autoriza
- `servicio` - Tipo: "Servicios Generales", "Poliza"
- `mantenimiento` - Tipo: "Preventivo", "Correctivo", "Diagnóstico", etc.
- `condicion` - Estado: "1", "2", "3"
- `actividad` - Descripción detallada del trabajo (TEXT)
- `folio` - Folio corto
- `Nfolio` - Folio completo con prefijo
- `visita` - Número de visita: "1", "2", "3"
- `tipo` - Tipo de equipo (opcional)

### Fechas
- `salida` - Timestamp de cuando se realizó el servicio
- `fechas` - JSON con: mes, semana, dateF, inicio, fin

### Arrays JSON
- `conceptos` - Array de materiales/refacciones utilizadas
- `ciclos` - Array de información de ciclos de lavado (si aplica)
- `frio` - Array de mediciones de equipos de refrigeración (si aplica)  
- `tipoactividades` - Array de actividades específicas del tipo de equipo

### Estados UI
- `boton_deshabilitado` - Boolean para UI
- `procesando_accion` - Boolean para UI

### Timestamps Automáticos
- `created_at`, `updated_at`

---

## Relaciones del Modelo

```php
// Un servicio pertenece a un usuario (opcional)
$servicio->usuario; // BelongsTo User

// Un servicio pertenece a un equipo (opcional)  
$servicio->equipo; // BelongsTo Product
```

---

## Endpoints (admin)

Base: `/api/admin/servicios`

### 1. `GET /api/admin/servicios`
Lista servicios con paginación.

**Query Parameters:**
- `status` - Filtrar por estado
- `servicio` - Filtrar por tipo de servicio  
- `mantenimiento` - Filtrar por tipo de mantenimiento
- `page` - Página (default: 1)

**Response:**
```json
{
  "data": [
    {
      "id": 1,
      "firebase_id": "002cF9FfupX7xE60lHxs",
      "usuario_id": 1,
      "equipo_id": 2,
      "cliente_data": {
        "nombre": "GM Silao",
        "telefono": "(000) 000 - 0000", 
        "email": "Gmsilao@gm.com",
        "direccion": "Silao",
        "ciudad": "Silao",
        "responsable": "Raymundo E"
      },
      "status": "Abierto",
      "servicio": "Poliza",
      "mantenimiento": "Preventivo",
      "folio": "0126",
      "Nfolio": "6940126",
      "actividad": "Se realiza Revisión de equipo...",
      "usuario": {
        "id": 1,
        "name": "Jacinto Jiménez",
        "email": "jacinto.jimenez@serviram.com.mx"
      },
      "equipo": {
        "id": 2,
        "nombre": "T1 Barra caliente 3",
        "marca": "nd"
      }
    }
  ],
  "current_page": 1,
  "total": 150
}
```

### 2. `POST /api/admin/servicios`
Crear nuevo servicio.

**Body ejemplo:**
```json
{
  "firebase_id": "nuevo123",
  "usuario_id": 1,
  "equipo_id": 2,
  "cliente_data": {
    "nombre": "Cliente Ejemplo",
    "telefono": "(444) 123-4567",
    "email": "cliente@ejemplo.com",
    "direccion": "Calle Ejemplo 123",
    "ciudad": "San Luis Potosí",
    "responsable": "Juan Pérez"
  },
  "status": "Abierto",
  "servicio": "Servicios Generales",
  "mantenimiento": "Correctivo",
  "condicion": "2",
  "actividad": "Se realizó reparación del equipo...",
  "folio": "0001",
  "Nfolio": "ABC0001",
  "visita": "1",
  "salida": "2026-03-26T10:30:00Z",
  "fechas": {
    "mes": 3,
    "semana": 13,
    "dateF": "2026-03-26 10:30"
  },
  "conceptos": [
    {
      "Descripcion": "Resistencia 220v",
      "Cantidad": 2,
      "Unidad": "piezas",
      "Tipo": "Instaló Refacción"
    }
  ]
}
```

### 3. `GET /api/admin/servicios/{servicio}`
Ver servicio por ID con relaciones.

### 4. `PUT /api/admin/servicios/{servicio}`
Actualizar servicio.

### 5. `DELETE /api/admin/servicios/{servicio}`
Eliminar servicio.

---

## Importación desde Firebase

### Comando

```bash
php artisan servicios:import-firebase storage/app/imports/servicios.json --dry-run
php artisan servicios:import-firebase storage/app/imports/servicios.json
```

### Formato esperado del JSON

```json
[
  {
    "id": "002cF9FfupX7xE60lHxs",
    "data": {
      "status": "Abierto",
      "conceptos": [],
      "fechas": {
        "mes": 10,
        "semana": 41,
        "dateF": "2024-10-04 17:24"
      },
      "cliente": {
        "telefono": "(000) 000 - 0000",
        "direccion": "Silao", 
        "responsable": "Raymundo E",
        "email": "Gmsilao@gm.com",
        "nombre": "GM Silao",
        "ciudad": "Silao"
      },
      "autorizacion": "Raymundo E",
      "salida": {
        "seconds": 1728013020,
        "nanoseconds": 0
      },
      "usuario": {
        "id": "694nKOMQQN94zrIWYlwV"
      },
      "folio": "6940126",
      "equipo": {
        "id": "SDJcoKxgGBZ70vpvaBOy"
      },
      "ciclos": [],
      "condicion": "3", 
      "frio": [],
      "actividad": "Se realiza Revisión...",
      "Nfolio": "0126",
      "mantenimiento": "Preventivo",
      "servicio": "Poliza"
    }
  }
]
```

### Lógica de Importación

1. **Relación Usuario**: Busca en `users` por `firebase_id = data.usuario.id`
   - Si existe: asigna `usuario_id`
   - Si no existe: `usuario_id = null`

2. **Relación Equipo**: Busca en `products` por `firebase_id = data.equipo.id`  
   - Si existe: asigna `equipo_id`
   - Si no existe: `equipo_id = null`

3. **Cliente**: Se guarda completo en `cliente_data` como JSON

4. **Fechas**: Convierte Firebase timestamps (`seconds`) a DateTime

5. **Duplicados**: Siempre hace `CREATE`, nunca deduplica

---

## Uso en frontend (axios)

```js
// Listar servicios con filtros
const { data } = await api.get('/admin/servicios', {
  params: { 
    status: 'Abierto',
    servicio: 'Poliza',
    page: 1 
  }
})

// Crear servicio
const payload = {
  usuario_id: 1,
  equipo_id: 2, 
  cliente_data: {
    nombre: 'Cliente Test',
    telefono: '444-1234',
    email: 'test@test.com'
  },
  servicio: 'Servicios Generales',
  mantenimiento: 'Preventivo',
  actividad: 'Mantenimiento realizado...'
}
await api.post('/admin/servicios', payload)

// Actualizar servicio
await api.put(`/admin/servicios/${id}`, {
  status: 'Cerrado',
  actividad: 'Servicio completado'
})

// Eliminar servicio  
await api.delete(`/admin/servicios/${id}`)
```

---

## Integración con tu código Quasar existente

Para conectar el backend con tu formulario:

```js
// Al guardar servicio (reemplazar Firebase addDoc)
const servicioData = {
  usuario_id: Usuario.value.id, // ID local del usuario
  equipo_id: data.equipo.id,    // ID local del equipo  
  cliente_data: {
    nombre: data.cliente,
    telefono: data.telefono,
    email: data.email,
    direccion: data.direccion,
    ciudad: data.ciudad,
    responsable: data.responsable
  },
  servicio: data.servicio,
  mantenimiento: data.mantenimiento,
  condicion: data.condicion,
  actividad: data.actividad,
  folio: formattedFolio,
  Nfolio: data.folio,
  visita: data.visita,
  conceptos: data.costos,
  ciclos: data.ciclos ? data.datosciclo : [],
  frio: data.fria ? data.datosfrio : [],
  tipoactividades: formData.value,
  tipo: data.tipos?.name || null,
  fechas: data.datos,
  salida: data.datos.dateF
}

await api.post('/admin/servicios', servicioData)
```

---

## Validaciones

- `usuario_id`: debe existir en tabla `users` (opcional)
- `equipo_id`: debe existir en tabla `products` (opcional)
- `cliente_data.email`: formato email válido (opcional)
- `salida`: formato de fecha válido (opcional)
- `fechas`, `conceptos`, `ciclos`, etc.: deben ser arrays válidos (opcional)

---

## Consideraciones Importantes

1. **Foreign Keys Opcionales**: Si el usuario/equipo referenciado se elimina, el servicio mantiene `usuario_id=null` o `equipo_id=null`

2. **Cliente Embebido**: No se crea relación FK con clientes, se guarda directamente en JSON  

3. **Duplicados Permitidos**: La importación siempre crea registros nuevos

4. **Paginación**: El endpoint index usa paginación de 50 elementos por página

5. **Timestamps Firebase**: Se convierten automáticamente de `seconds` a DateTime