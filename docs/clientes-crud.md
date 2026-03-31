# Clientes CRUD + Importación Firebase

Este módulo es independiente de `contacts` (chat).  
Se usa la tabla `clientes` con su propio modelo `Cliente`.

## Campos (tabla `clientes`)

- `id`
- `firebase_id` (nullable, con índice; se permiten repetidos)
- `compania` (index)
- `contacto` (nullable)
- `responsable` (nullable)
- `telefono` (nullable)
- `email` (nullable)
- `ciudad` (nullable)
- `direccion` (nullable)
- `created_at`, `updated_at`

## Endpoints (admin)

Base: `/api/admin/clientes`

1. `GET /api/admin/clientes`
- Lista todos los clientes.

2. `POST /api/admin/clientes`
- Crea cliente.

Body ejemplo:
```json
{
  "firebase_id": "04nMNnhtDfgCw5dUobdR",
  "compania": "Brewjoy",
  "contacto": "jaciel",
  "responsable": "jaciel",
  "telefono": "(444) 444 - 5678",
  "email": "Globalcher@gmail.com",
  "ciudad": "San José de Iturbide gto",
  "direccion": "Pl. Principal 27, Zona Centro"
}
```

3. `GET /api/admin/clientes/{cliente}`
- Ver un cliente por ID.

4. `PUT /api/admin/clientes/{cliente}`
- Actualizar cliente.

5. `DELETE /api/admin/clientes/{cliente}`
- Eliminar cliente.

## Importación desde Firebase

Comando:

```bash
php artisan clientes:import-firebase storage/app/imports/clientes.json --dry-run
php artisan clientes:import-firebase storage/app/imports/clientes.json
```

### Formato esperado del JSON

```json
[
  {
    "id": "04nMNnhtDfgCw5dUobdR",
    "data": {
      "responsable": "jaciel",
      "direccion": "Pl. Principal 27, Zona Centro",
      "telefono": "(444) 444 - 5678",
      "contacto": "jaciel",
      "ciudad": "San José de Iturbide gto",
      "compania": "Brewjoy",
      "email": "Globalcher@gmail.com"
    }
  }
]
```

### Regla de importación

- Siempre hace `CREATE` por cada fila del JSON.
- No deduplica por `firebase_id`, `compania` ni ningún otro campo.
- Si ejecutas el comando dos veces, se insertan duplicados (comportamiento intencional).

## Uso rápido en frontend (axios)

```js
// listar
const { data } = await api.get('/admin/clientes')

// crear
await api.post('/admin/clientes', payload)

// actualizar
await api.put(`/admin/clientes/${id}`, payload)

// eliminar
await api.delete(`/admin/clientes/${id}`)
```
