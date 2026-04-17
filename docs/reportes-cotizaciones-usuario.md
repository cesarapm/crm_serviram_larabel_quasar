# Reportes de Cotizaciones por Usuario - API

## Descripción

Estos endpoints proporcionan reportes de cotizaciones agrupados por usuario, mostrando cuántas cotizaciones ha creado cada usuario en diferentes períodos de tiempo (diario, semanal, mensual).

## Endpoints

### 1. Reporte Diario de Cotizaciones por Usuario

```
GET /api/reportes/cotizaciones-diarias
```

**Parámetros opcionales:**
- `fecha` (string): Fecha en formato YYYY-MM-DD. Por defecto: fecha actual.
- `usuario_id` (integer): ID específico de usuario para filtrar.

**Ejemplo de petición:**
```javascript
// Reporte del día actual para todos los usuarios
const response = await axios.get('/api/reportes/cotizaciones-diarias');

// Reporte de fecha específica
const response = await axios.get('/api/reportes/cotizaciones-diarias', {
  params: { 
    fecha: '2026-04-16'
  }
});

// Reporte de usuario específico
const response = await axios.get('/api/reportes/cotizaciones-diarias', {
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
  "total_cotizaciones_dia": 8,
  "usuarios": [
    {
      "id": 5,
      "name": "Miguel A. Gallegos",
      "nickname": "srmiguel",
      "email": "jacinto.jimenez@serviram.com.mx",
      "cotizaciones_count": 4
    },
    {
      "id": 8,
      "name": "María González",
      "nickname": "mariag",
      "email": "maria@empresa.com",
      "cotizaciones_count": 3
    },
    {
      "id": 2,
      "name": "VENTAS SLP",
      "nickname": "srjacinto",
      "email": "jacinto1.jimenez@serviram.com.mx",
      "cotizaciones_count": 1
    }
  ]
}
```

### 2. Reporte Semanal de Cotizaciones por Usuario

```
GET /api/reportes/cotizaciones-semanales
```

**Parámetros opcionales:**
- `semana` (integer): Número de semana del año (1-53). Por defecto: semana actual.
- `anio` (integer): Año. Por defecto: año actual.
- `usuario_id` (integer): ID específico de usuario para filtrar.

**Ejemplo de petición:**
```javascript
// Reporte de la semana actual
const response = await axios.get('/api/reportes/cotizaciones-semanales');

// Reporte de semana específica
const response = await axios.get('/api/reportes/cotizaciones-semanales', {
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
  "total_cotizaciones_semana": 18,
  "usuarios": [
    {
      "id": 5,
      "name": "Miguel A. Gallegos",
      "nickname": "srmiguel",
      "email": "jacinto.jimenez@serviram.com.mx",
      "cotizaciones_count": 7
    },
    {
      "id": 2,
      "name": "VENTAS SLP",
      "nickname": "srjacinto",
      "email": "jacinto1.jimenez@serviram.com.mx",
      "cotizaciones_count": 6
    }
  ]
}
```

### 3. Reporte Mensual de Cotizaciones por Usuario

```
GET /api/reportes/cotizaciones-mensuales
```

**Parámetros opcionales:**
- `mes` (integer): Número de mes (1-12). Por defecto: mes actual.
- `anio` (integer): Año. Por defecto: año actual.
- `usuario_id` (integer): ID específico de usuario para filtrar.

**Ejemplo de petición:**
```javascript
// Reporte del mes actual
const response = await axios.get('/api/reportes/cotizaciones-mensuales');

// Reporte de mes específico
const response = await axios.get('/api/reportes/cotizaciones-mensuales', {
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
  "total_cotizaciones_mes": 45,
  "usuarios": [
    {
      "id": 5,
      "name": "Miguel A. Gallegos",
      "nickname": "srmiguel",
      "email": "jacinto.jimenez@serviram.com.mx",
      "cotizaciones_count": 15
    },
    {
      "id": 2,
      "name": "VENTAS SLP",
      "nickname": "srjacinto",
      "email": "jacinto1.jimenez@serviram.com.mx",
      "cotizaciones_count": 12
    }
  ]
}
```

## Uso en Frontend (Vue/Quasar)

### Componente de Ejemplo

```vue
<template>
  <div>
    <q-card class="q-mb-md">
      <q-card-section>
        <div class="text-h6">Reportes de Cotizaciones por Usuario</div>
        <div class="row q-gutter-md">
          <q-select
            v-model="tipoReporte"
            :options="opcionesTipo"
            label="Tipo de Reporte"
            class="col-3"
            @update:model-value="cargarReporte"
          />
          
          <q-input
            v-if="tipoReporte === 'diario'"
            v-model="fecha"
            type="date"
            label="Fecha"
            class="col-3"
            @update:model-value="cargarReporte"
          />
          
          <div v-if="tipoReporte === 'semanal'" class="col-6 row q-gutter-sm">
            <q-input
              v-model="semana"
              type="number"
              label="Semana"
              min="1"
              max="53"
              class="col"
              @update:model-value="cargarReporte"
            />
            <q-input
              v-model="anio"
              type="number"
              label="Año"
              class="col"
              @update:model-value="cargarReporte"
            />
          </div>
          
          <div v-if="tipoReporte === 'mensual'" class="col-6 row q-gutter-sm">
            <q-select
              v-model="mes"
              :options="opcionesMeses"
              label="Mes"
              class="col"
              @update:model-value="cargarReporte"
            />
            <q-input
              v-model="anio"
              type="number"
              label="Año"
              class="col"
              @update:model-value="cargarReporte"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Indicadores principales -->
    <div class="row q-gutter-md q-mb-md">
      <q-card class="col">
        <q-card-section class="text-center">
          <div class="text-h4 text-primary">{{ totalUsuarios }}</div>
          <div class="text-subtitle2">Usuarios con Cotizaciones</div>
        </q-card-section>
      </q-card>
      
      <q-card class="col">
        <q-card-section class="text-center">
          <div class="text-h4 text-secondary">{{ totalCotizaciones }}</div>
          <div class="text-subtitle2">Total Cotizaciones</div>
        </q-card-section>
      </q-card>
      
      <q-card class="col">
        <q-card-section class="text-center">
          <div class="text-h4 text-positive">{{ promedioUsuario }}</div>
          <div class="text-subtitle2">Promedio por Usuario</div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Tabla de resultados -->
    <q-table
      :rows="usuarios"
      :columns="columnas"
      title="Cotizaciones por Usuario"
      row-key="id"
      :pagination="{rowsPerPage: 0}"
    >
      <!-- Slot para ranking -->
      <template v-slot:body-cell-ranking="props">
        <q-td :props="props">
          <q-badge 
            :color="getRankingColor(props.rowIndex)"
            :label="'#' + (props.rowIndex + 1)"
          />
        </q-td>
      </template>
    </q-table>
  </div>
</template>

<script>
import { ref, computed, onMounted } from 'vue'
import { api } from 'src/boot/axios'

export default {
  name: 'ReportesCotizaciones',
  setup() {
    const tipoReporte = ref('diario')
    const fecha = ref(new Date().toISOString().split('T')[0])
    const semana = ref(new Date().getWeek())
    const mes = ref(new Date().getMonth() + 1)
    const anio = ref(new Date().getFullYear())
    
    const usuarios = ref([])
    const totalUsuarios = ref(0)
    const totalCotizaciones = ref(0)
    
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
    ]\n    \n    const columnas = [\n      { name: 'ranking', label: '#', field: 'ranking', align: 'center' },\n      { name: 'name', label: 'Nombre', field: 'name', align: 'left', sortable: true },\n      { name: 'nickname', label: 'Usuario', field: 'nickname', align: 'left' },\n      { \n        name: 'cotizaciones_count', \n        label: 'Cotizaciones', \n        field: 'cotizaciones_count', \n        align: 'center', \n        sortable: true,\n        sort: (a, b) => b - a\n      }\n    ]\n    \n    const promedioUsuario = computed(() => {\n      if (totalUsuarios.value === 0) return '0.0'\n      return (totalCotizaciones.value / totalUsuarios.value).toFixed(1)\n    })\n    \n    const getRankingColor = (index) => {\n      if (index === 0) return 'amber'\n      if (index === 1) return 'grey-7'\n      if (index === 2) return 'brown'\n      return 'grey-5'\n    }\n    \n    const cargarReporte = async () => {\n      try {\n        let endpoint = '/api/reportes/cotizaciones-'\n        let params = {}\n        \n        if (tipoReporte.value === 'diario') {\n          endpoint += 'diarias'\n          params.fecha = fecha.value\n        } else if (tipoReporte.value === 'semanal') {\n          endpoint += 'semanales'\n          params.semana = semana.value\n          params.anio = anio.value\n        } else if (tipoReporte.value === 'mensual') {\n          endpoint += 'mensuales'\n          params.mes = mes.value\n          params.anio = anio.value\n        }\n        \n        const { data } = await api.get(endpoint, { params })\n        \n        usuarios.value = data.usuarios\n        totalUsuarios.value = data.total_usuarios\n        totalCotizaciones.value = data.total_cotizaciones_dia || data.total_cotizaciones_semana || data.total_cotizaciones_mes\n        \n      } catch (error) {\n        console.error('Error cargando reporte de cotizaciones:', error)\n        // Manejo de errores\n      }\n    }\n    \n    onMounted(() => {\n      cargarReporte()\n    })\n    \n    return {\n      tipoReporte,\n      fecha,\n      semana,\n      mes,\n      anio,\n      usuarios,\n      totalUsuarios,\n      totalCotizaciones,\n      promedioUsuario,\n      opcionesTipo,\n      opcionesMeses,\n      columnas,\n      cargarReporte,\n      getRankingColor\n    }\n  }\n}\n</script>\n```\n\n## Métricas y Análisis\n\n### Indicadores Clave (KPIs)\n\n1. **Productividad por Usuario:**\n   - Número total de cotizaciones creadas por usuario\n   - Promedio de cotizaciones por usuario en el período\n   - Ranking de usuarios más productivos\n\n2. **Tendencias Temporales:**\n   - Comparación día a día, semana a semana, mes a mes\n   - Identificación de picos y valles de actividad\n   - Usuarios más consistentes en la creación de cotizaciones\n\n3. **Distribución del Trabajo:**\n   - Porcentaje de cotizaciones por usuario\n   - Identificación de carga de trabajo desbalanceada\n   - Usuarios que requieren más trabajo o apoyo\n\n### Casos de Uso para el Frontend\n\n1. **Dashboard de Ventas:**\n   ```javascript\n   // Mostrar top 3 usuarios del mes\n   const topUsuarios = data.usuarios.slice(0, 3)\n   ```\n\n2. **Alertas de Productividad:**\n   ```javascript\n   // Alertar si un usuario no tiene cotizaciones en el día\n   const usuariosSinCotizaciones = allUsers.filter(user => \n     !data.usuarios.find(u => u.id === user.id)\n   )\n   ```\n\n3. **Comparaciones de Rendimiento:**\n   ```javascript\n   // Comparar período actual vs anterior\n   const [actual, anterior] = await Promise.all([\n     api.get('/api/reportes/cotizaciones-mensuales', { params: { mes: 4, anio: 2026 } }),\n     api.get('/api/reportes/cotizaciones-mensuales', { params: { mes: 3, anio: 2026 } })\n   ])\n   ```\n\n## Consideraciones Técnicas\n\n1. **Autenticación**: Todos los endpoints requieren autenticación (`auth:sanctum`)\n\n2. **Performance**: \n   - Consultas optimizadas por usuario\n   - Consider agregar índices en `cotizaciones.usuario_id` y `cotizaciones.created_at`\n   - Para volúmenes grandes, implementar paginación\n\n3. **Datos**: \n   - Usa `created_at` para determinar cuándo se creó la cotización\n   - Solo incluye usuarios que tienen al menos una cotización en el período\n   - Ordenamiento por cantidad descendente\n\n4. **Escalabilidad**:\n   - Para equipos grandes (>50 usuarios), considera implementar caché\n   - Agregar límites de consulta si es necesario\n   - Implementar filtros adicionales por estado de cotización\n\n5. **Extensiones Futuras**:\n   - Agregar filtros por estado de cotización (pendiente, aprobada, rechazada)\n   - Incluir montos totales de las cotizaciones\n   - Métricas de conversión (cotizaciones a ventas)\n   - Reportes comparativos entre períodos