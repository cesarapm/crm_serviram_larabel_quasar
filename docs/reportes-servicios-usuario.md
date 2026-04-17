# Reportes de Servicios por Usuario - API

## Descripción

Estos endpoints proporcionan reportes de servicios agrupados por usuario, incluyendo tanto **servicios** como **gm_servicios**. Cada endpoint devuelve estadísticas agregadas y detalles por usuario.

## Endpoints

### 1. Reporte Diario por Usuario

```
GET /api/reportes/servicios-diarios
```

**Parámetros opcionales:**
- `fecha` (string): Fecha en formato YYYY-MM-DD. Por defecto: fecha actual.
- `usuario_id` (integer): ID específico de usuario para filtrar.

**Ejemplo de petición:**
```javascript
// Reporte del día actual para todos los usuarios
const response = await axios.get('/api/reportes/servicios-diarios');

// Reporte de fecha específica
const response = await axios.get('/api/reportes/servicios-diarios', {
  params: { 
    fecha: '2026-04-16'
  }
});

// Reporte de usuario específico
const response = await axios.get('/api/reportes/servicios-diarios', {
  params: { 
    fecha: '2026-04-16',
    usuario_id: 5
  }
});
```

**Ejemplo de respuesta:**
```json
{
  "fecha": "2026-04-16",
  "total_usuarios": 3,
  "total_servicios_dia": 12,
  "usuarios": [
    {
      "id": 5,
      "name": "Juan Pérez",
      "nickname": "juanp",
      "email": "juan@empresa.com",
      "servicios_count": 4,
      "gm_servicios_count": 2,
      "total_servicios": 6
    },
    {
      "id": 8,
      "name": "María González",
      "nickname": "mariag",
      "email": "maria@empresa.com",
      "servicios_count": 2,
      "gm_servicios_count": 1,
      "total_servicios": 3
    }
  ]
}
```

### 2. Reporte Semanal por Usuario

```
GET /api/reportes/servicios-semanales
```

**Parámetros opcionales:**
- `semana` (integer): Número de semana del año (1-53). Por defecto: semana actual.
- `anio` (integer): Año. Por defecto: año actual.
- `usuario_id` (integer): ID específico de usuario para filtrar.

**Ejemplo de petición:**
```javascript
// Reporte de la semana actual
const response = await axios.get('/api/reportes/servicios-semanales');

// Reporte de semana específica
const response = await axios.get('/api/reportes/servicios-semanales', {
  params: { 
    semana: 15,
    anio: 2026
  }
});
```

**Ejemplo de respuesta:**
```json
{
  "semana": 15,
  "anio": 2026,
  "total_usuarios": 4,
  "total_servicios_semana": 28,
  "usuarios": [
    {
      "id": 5,
      "name": "Juan Pérez",
      "nickname": "juanp",
      "email": "juan@empresa.com",
      "servicios_count": 8,
      "gm_servicios_count": 4,
      "total_servicios": 12
    }
  ]
}
```

### 3. Reporte Mensual por Usuario

```
GET /api/reportes/servicios-mensuales
```

**Parámetros opcionales:**
- `mes` (integer): Número de mes (1-12). Por defecto: mes actual.
- `anio` (integer): Año. Por defecto: año actual.
- `usuario_id` (integer): ID específico de usuario para filtrar.

**Ejemplo de petición:**
```javascript
// Reporte del mes actual
const response = await axios.get('/api/reportes/servicios-mensuales');

// Reporte de mes específico
const response = await axios.get('/api/reportes/servicios-mensuales', {
  params: { 
    mes: 4,
    anio: 2026
  }
});
```

**Ejemplo de respuesta:**
```json
{
  "mes": 4,
  "anio": 2026,
  "total_usuarios": 6,
  "total_servicios_mes": 85,
  "usuarios": [
    {
      "id": 5,
      "name": "Juan Pérez",
      "nickname": "juanp",
      "email": "juan@empresa.com",
      "servicios_count": 20,
      "gm_servicios_count": 8,
      "total_servicios": 28
    }
  ]
}
```

## Uso en Frontend (Vue/Quasar)

### Componente de Ejemplo

```vue
<template>
  <div>
    <q-select
      v-model="tipoReporte"
      :options="opcionesTipo"
      label="Tipo de Reporte"
      @update:model-value="cargarReporte"
    />
    
    <q-input
      v-if="tipoReporte === 'diario'"
      v-model="fecha"
      type="date"
      label="Fecha"
      @update:model-value="cargarReporte"
    />
    
    <div v-if="tipoReporte === 'semanal'" class="row">
      <q-input
        v-model="semana"
        type="number"
        label="Semana"
        class="col-6"
        @update:model-value="cargarReporte"
      />
      <q-input
        v-model="anio"
        type="number"
        label="Año"
        class="col-6"
        @update:model-value="cargarReporte"
      />
    </div>
    
    <div v-if="tipoReporte === 'mensual'" class="row">
      <q-select
        v-model="mes"
        :options="opcionesMeses"
        label="Mes"
        class="col-6"
        @update:model-value="cargarReporte"
      />
      <q-input
        v-model="anio"
        type="number"
        label="Año"
        class="col-6"
        @update:model-value="cargarReporte"
      />
    </div>

    <!-- Tabla de resultados -->
    <q-table
      :rows="usuarios"
      :columns="columnas"
      title="Reporte de Servicios por Usuario"
      row-key="id"
    />
    
    <!-- Resumen -->
    <q-card class="q-mt-md">
      <q-card-section>
        <div class="text-h6">Resumen</div>
        <p>Total usuarios con servicios: {{ totalUsuarios }}</p>
        <p>Total servicios en el período: {{ totalServicios }}</p>
      </q-card-section>
    </q-card>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { api } from 'src/boot/axios'

export default {
  name: 'ReporteServiciosUsuario',
  setup() {
    const tipoReporte = ref('diario')
    const fecha = ref(new Date().toISOString().split('T')[0])
    const semana = ref(new Date().getWeek())
    const mes = ref(new Date().getMonth() + 1)
    const anio = ref(new Date().getFullYear())
    
    const usuarios = ref([])
    const totalUsuarios = ref(0)
    const totalServicios = ref(0)
    
    const opcionesTipo = [
      { label: 'Diario', value: 'diario' },
      { label: 'Semanal', value: 'semanal' },
      { label: 'Mensual', value: 'mensual' }
    ]
    
    const opcionesMeses = [
      { label: 'Enero', value: 1 },
      { label: 'Febrero', value: 2 },
      { label: 'Marzo', value: 3 },
      { label: 'Abril', value: 4 },
      { label: 'Mayo', value: 5 },
      { label: 'Junio', value: 6 },
      { label: 'Julio', value: 7 },
      { label: 'Agosto', value: 8 },
      { label: 'Septiembre', value: 9 },
      { label: 'Octubre', value: 10 },
      { label: 'Noviembre', value: 11 },
      { label: 'Diciembre', value: 12 }
    ]
    
    const columnas = [
      { name: 'name', label: 'Nombre', field: 'name', align: 'left' },
      { name: 'nickname', label: 'Usuario', field: 'nickname', align: 'left' },
      { name: 'servicios_count', label: 'Servicios', field: 'servicios_count', align: 'center' },
      { name: 'gm_servicios_count', label: 'GM Servicios', field: 'gm_servicios_count', align: 'center' },
      { name: 'total_servicios', label: 'Total', field: 'total_servicios', align: 'center', sortable: true }
    ]
    
    const cargarReporte = async () => {
      try {
        let endpoint = '/api/reportes/servicios-'
        let params = {}
        
        if (tipoReporte.value === 'diario') {
          endpoint += 'diarios'
          params.fecha = fecha.value
        } else if (tipoReporte.value === 'semanal') {
          endpoint += 'semanales'
          params.semana = semana.value
          params.anio = anio.value
        } else if (tipoReporte.value === 'mensual') {
          endpoint += 'mensuales'
          params.mes = mes.value
          params.anio = anio.value
        }
        
        const { data } = await api.get(endpoint, { params })
        
        usuarios.value = data.usuarios
        totalUsuarios.value = data.total_usuarios || data.total_usuarios_semana || data.total_usuarios_mes
        totalServicios.value = data.total_servicios_dia || data.total_servicios_semana || data.total_servicios_mes
        
      } catch (error) {
        console.error('Error cargando reporte:', error)
        // Manejo de errores
      }
    }
    
    onMounted(() => {
      cargarReporte()
    })
    
    return {
      tipoReporte,
      fecha,
      semana,
      mes,
      anio,
      usuarios,
      totalUsuarios,
      totalServicios,
      opcionesTipo,
      opcionesMeses,
      columnas,
      cargarReporte
    }
  }
}
</script>
```

## Consideraciones

1. **Autenticación**: Todos los endpoints requieren autenticación (`auth:sanctum`).

2. **Performance**: Los reportes usan joins optimizados, pero para grandes volúmenes de datos considera agregar índices en:
   - `servicios.usuario_id`
   - `servicios.created_at`
   - `gm_servicios.usuario_id`
   - `gm_servicios.created_at`

3. **Filtros adicionales**: Puedes extender los endpoints para incluir filtros adicionales como estado del servicio, tipo de mantenimiento, etc.

4. **Exportación**: Considera agregar endpoints adicionales para exportar a Excel/CSV si es necesario.

5. **Caché**: Para reportes que se consultan frecuentemente, considera implementar caché.