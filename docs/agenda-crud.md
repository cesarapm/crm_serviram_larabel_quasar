# Agenda CRUD + Importación Firebase

Módulo para eventos de agenda (colección Firebase: `agenda`) vinculados opcionalmente a `ordenes`.

## Campos (tabla `agendas`)

- `id`
- `firebase_id` (ID del documento en Firebase)
- `orden_id` (FK opcional a `ordenes.id`)
- `id_orden_firebase` (ID de orden en Firebase, para trazabilidad)
- `start` (datetime normalizado)
- `start_raw` (string original de Firebase)
- `fecha` (string mostrado en UI)
- `all_day` (boolean)
- `text_color`
- `title`
- `equipo_data` (JSON)
- `block` (boolean)
- `estatus` (boolean)
- `created_at`, `updated_at`

## Endpoints (admin)

Base: `/api/admin/agenda`

- `GET /api/admin/agenda`
- `GET /api/admin/agenda-list`
- `POST /api/admin/agenda`
- `GET /api/admin/agenda/{agenda}`
- `PUT /api/admin/agenda/{agenda}`
- `DELETE /api/admin/agenda/{agenda}`
- `GET /api/admin/agenda/orden/{orden}` (agenda por orden)

### Filtros disponibles en index

- `orden_id`
- `id_orden_firebase`
- `title`
- `estatus` (true/false)
- `start_from` (YYYY-MM-DD)
- `start_to` (YYYY-MM-DD)
- `paginate=false` para respuesta sin paginación

## Importación Firebase

```bash
php artisan agenda:import-firebase storage/app/imports/agenda.json --dry-run
php artisan agenda:import-firebase storage/app/imports/agenda.json
```

### Regla de relación con orden

- Toma `data.id_orden`.
- Busca en `ordenes.firebase_id`.
- Si encuentra orden, asigna `orden_id`.
- Si no, deja `orden_id = null` y conserva `id_orden_firebase`.

## Orden recomendado de migración

1. Importar usuarios
2. Importar ordenes
3. Importar agenda

```bash
php artisan users:import-firebase storage/app/imports/users.json
php artisan ordenes:import-firebase storage/app/imports/Orden.json
php artisan agenda:import-firebase storage/app/imports/agenda.json
```
