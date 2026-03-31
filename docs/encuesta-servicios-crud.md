# Encuesta Servicios CRUD

Modulo para registrar encuestas de satisfaccion asociadas a servicios normales y GM sin colision de IDs.

## Solucion de IDs repetidos

Se agrega el campo origen para desambiguar el servicio de referencia:
- servicio
- gm_servicio

Con esto, un mismo servicio_firebase_id puede existir en ambos contextos sin conflicto.

## Campos (tabla encuesta_servicios)

- id
- firebase_id (ID del documento de Firebase, nullable unique)
- origen (servicio o gm_servicio)
- servicio_firebase_id (ID del servicio en Firebase)
- servicio_id (FK opcional a servicios.id)
- gm_servicio_id (FK opcional a gm_servicios.id)
- calificacion (decimal)
- fecha (timestamp)
- created_at
- updated_at

## Endpoints (admin)

Base: /api/admin/encuesta-servicios

1. GET /api/admin/encuesta-servicios
- Filtros: origen, servicio_firebase_id
- paginate=false para arreglo simple

2. GET /api/admin/encuesta-servicios-list

3. POST /api/admin/encuesta-servicios
Body ejemplo:
```json
{
  "firebase_id": "03o0soAKl3O6bHP0C3m2",
  "origen": "servicio",
  "servicio_firebase_id": "NXsnRZ2GfgKeeONzBWQO",
  "calificacion": 1,
  "fecha": "2025-11-28T18:39:16.986Z"
}
```

4. GET /api/admin/encuesta-servicios/{encuestasServicio}

5. PUT /api/admin/encuesta-servicios/{encuestasServicio}

6. DELETE /api/admin/encuesta-servicios/{encuestasServicio}

## Importacion Firebase

```bash
php artisan encservicios:import-firebase storage/app/imports/Encservicios.json --dry-run
php artisan encservicios:import-firebase storage/app/imports/Encservicios.json
```

Regla de importacion:
- Busca servicio_firebase_id primero en servicios y luego en gm_servicios.
- Si existe en ambos, se marca como ambiguo y se salta.
- Si existe en uno, guarda origen y FK correspondiente.
