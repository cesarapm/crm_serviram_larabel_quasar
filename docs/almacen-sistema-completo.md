# Sistema de Almacén - Documentación Completa

## 📋 Resumen del Sistema

El sistema de almacén integra **3 módulos principales** trabajando de forma coordinada:

### 🏗️ **1. Racks (Estanterías)**
- **Modelo**: `Rack`
- **Archivo JSON**: `storage/app/imports/racks.json` 
- **Función**: Organización física del almacén por ubicaciones

### 📦 **2. Items (Inventario)**  
- **Modelo**: `Item`
- **Archivo JSON**: `storage/app/imports/almacen.json`
- **Función**: Control de stock, productos, refacciones, herramientas

### 📊 **3. Movimientos (Historial)**
- **Modelo**: `MovimientoInventario` 
- **Archivo JSON**: `storage/app/imports/movimientos.json` (actualmente vacío)
- **Función**: Trazabilidad completa de entradas/salidas

---

## 🚀 Importación de Datos

### **Método 1: Comandos Individuales**

```bash
# Importar racks primero (recomendado)
php artisan almacen:import-racks

# Importar items de almacén  
php artisan almacen:import-items

# Importar movimientos de inventario
php artisan almacen:import-movimientos

# También disponibles desde console.php:
php artisan import:racks
php artisan import:almacen
php artisan import:movimientos
```

### **Método 2: Importación Completa (Recomendado)**

```bash
# Importa todo el sistema de almacén de una vez
php artisan almacen:import-all

# Importa todo sin confirmaciones interactivas
php artisan almacen:import-all --force
```

**¿Qué hace este comando?**
1. ✅ Importa todos los racks desde `racks.json`
2. ✅ Importa todos los items desde `almacen.json`  
3. ✅ Importa movimientos desde `movimientos.json`
4. ✅ Establece relaciones entre items y racks
5. ✅ Muestra resumen del sistema importado

---

## 🌐 Frontend - API Endpoints

### **📊 Dashboard Principal**
```javascript
// Estadísticas generales del almacén
GET /api/admin/items-estadisticas
// Retorna: total_items, tipos_count, bajo_stock, valor_total, etc.

// Estadísticas de racks
GET /api/admin/racks-estadisticas  
// Retorna: ocupación, capacidades, estados de racks
```

### **📦 Gestión de Items**
```javascript
// Listar items con filtros
GET /api/admin/items?tipo=Refacción&rack=A&buscar=motor

// CRUD de items
POST /api/admin/items              // Crear
GET /api/admin/items/{id}          // Ver detalles
PUT /api/admin/items/{id}          // Actualizar  
DELETE /api/admin/items/{id}       // Eliminar

// Gestión de stock
PATCH /api/admin/items/{id}/ajustar-stock
Body: {
  "tipo": "entrada|salida|ajuste",
  "cantidad": 10,
  "observaciones": "Compra nueva"
}

// Historial de movimientos
GET /api/admin/items/{id}/movimientos
```

### **🏗️ Gestión de Racks**
```javascript
// Listar racks con ocupación
GET /api/admin/racks

// CRUD de racks  
POST /api/admin/racks              // Crear
GET /api/admin/racks/{id}          // Ver detalles + items
PUT /api/admin/racks/{id}          // Actualizar
DELETE /api/admin/racks/{id}       // Eliminar (solo si vacío)

// Items en un rack específico
GET /api/admin/racks/{id}/items?tipo=Herramienta
```

---

## 🎨 Estructura de Datos Frontend

### **Item (Producto/Refacción)**
```javascript
{
  "id": 1,
  "codigo": "REF001", 
  "nombre": "Motor eléctrico",
  "tipo": "Refacción",
  "marca": "SIEMENS",
  "stock": 15,
  "stock_minimo": 5,
  "stock_status": "normal|bajo_stock|sin_stock",
  "precio_unitario": 1250.00,
  "valor_total": 18750.00,
  "rack": "A",
  "ubicacion": "A-2",
  "rackRelacion": {
    "nombre": "A",
    "descripcion": "Consumibles",
    "estado": "medio"
  },
  "ultimoMovimiento": {
    "tipo_movimiento": "entrada",
    "cantidad": 5,
    "created_at": "2026-03-28T10:30:00"
  }
}
```

### **Rack (Estantería)**
```javascript
{
  "id": 1,
  "nombre": "A",
  "descripcion": "Consumibles", 
  "ubicacion": "Almacén principal",
  "niveles": 4,
  "capacidad": 1,
  "posiciones_por_nivel": 1,
  "capacidad_total": 4,
  "items_actuales": 8,
  "disponible": -4, // Puede estar sobrecargado
  "porcentaje_ocupacion": 200.0,
  "estado": "lleno|casi_lleno|medio|vacio",
  "valor_total": 25430.50,
  "items_por_tipo": [
    {"tipo": "Refacción", "cantidad": 5},
    {"tipo": "Insumo", "cantidad": 3}
  ]
}
```

### **Movimiento (Historial)**
```javascript
{
  "id": 1,
  "item_id": 15,
  "user_id": 1,
  "tipo_movimiento": "entrada",
  "tipo_movimiento_texto": "Entrada", 
  "cantidad": 10,
  "stock_anterior": 5,
  "stock_nuevo": 15,
  "diferencia": 10,
  "es_entrada": true,
  "es_salida": false,
  "observaciones": "Compra de proveedor XYZ",
  "created_at": "2026-03-28T10:30:00",
  "user": {
    "name": "Juan Pérez"
  },
  "item": {
    "codigo": "REF001",
    "nombre": "Motor eléctrico"
  }
}
```

---

## 🔧 Funcionalidades Avanzadas

### **Ajuste de Stock Automático**
Cuando se ajusta stock desde el frontend, automáticamente:
- ✅ Actualiza la tabla `items`
- ✅ Crea registro en `movimientos_inventario`  
- ✅ Registra usuario que hizo el cambio
- ✅ Guarda observaciones y timestamp

### **Relaciones Inteligentes**
- ✅ Items conectados a su rack físico
- ✅ Cálculo automático de ocupación de racks
- ✅ Estados dinámicos (lleno, medio, vacío)
- ✅ Historial completo de cada item

### **Validaciones de Negocio**
- ✅ No se puede eliminar rack con items
- ✅ Stock nunca es negativo
- ✅ Códigos únicos por item
- ✅ Nombres únicos por rack

---

## 🎯 Casos de Uso Frontend

### **Dashboard Principal**
```javascript
// Obtener métricas para el dashboard
const statistics = await fetch('/api/admin/items-estadisticas')
const rackStats = await fetch('/api/admin/racks-estadisticas')

// Mostrar:
// - Total items: 156
// - Bajo stock: 12 items
// - Valor total: $45,230.50
// - Racks llenos: 2/8
```

### **Lista de Items**
```javascript  
// Items con filtros y búsqueda
const items = await fetch('/api/admin/items?' + new URLSearchParams({
  rack: selectedRack,      // Filtro por rack
  tipo: selectedType,      // Filtro por tipo
  bajo_stock: true,        // Solo items con bajo stock
  buscar: searchTerm       // Búsqueda en código/nombre
}))
```

### **Gestión de Stock**
```javascript
// Entrada de mercancía
await fetch(`/api/admin/items/${itemId}/ajustar-stock`, {
  method: 'PATCH',
  body: JSON.stringify({
    tipo: 'entrada',
    cantidad: 50,
    observaciones: 'Compra pedido #1234'
  })
})

// Salida por uso en servicio
await fetch(`/api/admin/items/${itemId}/ajustar-stock`, {
  method: 'PATCH', 
  body: JSON.stringify({
    tipo: 'salida',
    cantidad: 2,
    observaciones: 'Usado en orden #5678'
  })
})
```

### **Vista de Rack**
```javascript
// Ver rack completo con items
const rack = await fetch(`/api/admin/racks/${rackId}`)
const itemsEnRack = await fetch(`/api/admin/racks/${rackId}/items`)

// Mostrar:
// - Información del rack (capacidad, ocupación)
// - Lista de items en el rack 
// - Filtros por tipo de item
```

---

## 📱 Components Frontend Recomendados

### **1. AlmacenDashboard.vue**
- Métricas generales
- Gráficas de ocupación  
- Items con bajo stock
- Racks más utilizados

### **2. ItemsList.vue** 
- Tabla de items con filtros
- Búsqueda instantánea
- Botones de acción rápida
- Estado de stock visual

### **3. RackMap.vue**
- Vista visual de racks
- Código de colores por ocupación
- Navegación a items del rack

### **4. StockAdjustModal.vue**
- Formulario de ajuste
- Tipos de movimiento
- Validación de cantidades
- Confirmación de cambios

### **5. MovementHistory.vue**  
- Historial de item específico
- Filtros por fecha/tipo
- Información del usuario
- Export a Excel/PDF

---

## ✅ Checklist de Implementación

### **Backend (Completado)**
- [x] Modelo Item con relaciones
- [x] Modelo Rack con cálculos 
- [x] Modelo MovimientoInventario
- [x] Migraciones de tablas
- [x] Controladores API
- [x] Rutas protegidas
- [x] Comandos de importación
- [x] Validaciones de negocio

### **Importación de Datos**
- [ ] Ejecutar `php artisan almacen:import-all`
- [ ] Verificar racks importados
- [ ] Verificar items importados  
- [ ] Verificar relaciones item-rack

### **Frontend (Por Implementar)**
- [ ] Dashboard de almacén
- [ ] CRUD de items
- [ ] CRUD de racks
- [ ] Ajuste de stock
- [ ] Historial de movimientos
- [ ] Reportes y estadísticas

---

## 🔗 Endpoints de Prueba

Una vez importados los datos, puedes probar:

```bash
# Ver todos los racks
curl -H "Authorization: Bearer TOKEN" \
  http://localhost/api/admin/racks

# Ver items del rack A
curl -H "Authorization: Bearer TOKEN" \
  http://localhost/api/admin/racks/1/items  

# Estadísticas del almacén
curl -H "Authorization: Bearer TOKEN" \
  http://localhost/api/admin/items-estadisticas
```

**¡El sistema está listo para integración frontend completa!** 🚀