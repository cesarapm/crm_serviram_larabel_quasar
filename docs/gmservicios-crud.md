# GmServicios CRUD

Modulo para manejar servicios GM con estructura equivalente a Servicios.

## Campos (tabla gm_servicios)

### Identificadores
- id
- firebase_id (nullable, index)

### Relaciones
- usuario_id -> users.id (nullable, on delete set null)
- equipo_id -> products.id (nullable, on delete set null)

### Cliente embebido (JSON)
- cliente_data (nombre, telefono, email, direccion, ciudad, responsable)

### Campos principales
- status
- autorizacion
- servicio
- mantenimiento
- condicion
- actividad
- folio
- Nfolio
- visita
- tipo

### Fechas y arreglos JSON
- salida
- fechas
- conceptos
- ciclos
- frio
- tipoactividades

### Estados UI
- boton_deshabilitado
- procesando_accion

### Timestamps
- created_at
- updated_at

---

## Endpoints (admin)

Base: /api/admin/gm-servicios

1. GET /api/admin/gm-servicios
- Lista con paginacion por defecto (50).
- Filtros: status, servicio, mantenimiento.
- paginate=false devuelve arreglo simple.

2. GET /api/admin/gm-servicios-list
- Lista completa sin paginacion.

3. POST /api/admin/gm-servicios
- Crea registro.
- Si llega usuario_id, incrementa users.lastfolio en +1 dentro de transaccion.

4. GET /api/admin/gm-servicios/{gmServicio}
- Obtiene un registro por ID.

5. PUT /api/admin/gm-servicios/{gmServicio}
- Actualiza registro.

6. DELETE /api/admin/gm-servicios/{gmServicio}
- Elimina registro.
