# Ordenes CRUD + Importación Firebase

Módulo para manejar órdenes de servicio (colección Firebase: `Orden`).

## Campos (tabla `ordenes`)

- `id`
- `firebase_id` (ID del documento en Firebase)
- `usuario_id` (FK opcional a `users.id`, resuelto por `users.firebase_id`)
- `usuario_data` (JSON del usuario embebido)
- `cliente_data` (JSON: nombre, telefono, email, direccion, ciudad, responsable)
- `fecha` (string, por compatibilidad con frontend)
- `fechas` (JSON con semana/mes/fecha Firestore)
- `servicio`
- `mantenimiento`
- `folio`
- `idfolio`
- `estatus` (boolean)
- `equipos` (JSON)
- `boton_deshabilitado` (boolean)
- `procesando_accion` (boolean)
- `created_at`, `updated_at`

## Endpoints (admin)

Base: `/api/admin/ordenes`

- `GET /api/admin/ordenes`
- `GET /api/admin/ordenes-list`
- `POST /api/admin/ordenes`
- `GET /api/admin/ordenes/{orden}`
- `PUT /api/admin/ordenes/{orden}`
- `DELETE /api/admin/ordenes/{orden}`

### Filtros disponibles en index

- `folio`
- `idfolio`
- `servicio`
- `mantenimiento`
- `estatus` (true/false)
- `paginate=false` para respuesta sin paginación

## Importación Firebase

```bash
php artisan ordenes:import-firebase storage/app/imports/Orden.json --dry-run
php artisan ordenes:import-firebase storage/app/imports/Orden.json
```

### Regla de relación de usuario

- Lee `data.usuario`.
- Si viene como objeto normal y tiene `id`, usa ese ID.
- Si viene como array indexado (`0`, `1`, ...), toma el primero con `id`.
- Busca en `users.firebase_id`.
- Si existe, asigna `usuario_id`; si no, deja `null` y conserva `usuario_data`.
