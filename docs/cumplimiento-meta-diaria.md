# Reporte de Cumplimiento de Meta Diaria - API

## Descripción

Este endpoint permite verificar el cumplimiento de la meta diaria de servicios por técnico. La meta establecida es de **4 servicios completados por técnico por día**.

## Endpoint

### Verificar Cumplimiento de Meta Diaria

```
GET /api/reportes/cumplimiento-meta
```

**Parámetros opcionales:**
- `fecha` (string): Fecha en formato YYYY-MM-DD. Por defecto: fecha actual.

**Ejemplo de petición:**
```javascript
// Verificar cumplimiento del día actual
const response = await axios.get('/api/reportes/cumplimiento-meta');

// Verificar cumplimiento de fecha específica
const response = await axios.get('/api/reportes/cumplimiento-meta', {
  params: { 
    fecha: '2026-04-16'
  }
});
```

**Ejemplo de respuesta:**
```json
{
  "fecha": "2026-04-16",
  "meta_diaria": 4,
  "tecnicos_con_servicios": 8,
  "tecnicos_que_cumplen": 3,
  "porcentaje_cumplimiento": 37.5,
  "total_servicios_del_dia": 22,
  "detalle_tecnicos": [
    {
      "id": 5,
      "name": "Miguel A. Gallegos",
      "nickname": "srmiguel",
      "email": "jacinto.jimenez@serviram.com.mx",
      "servicios_count": 3,
      "gm_servicios_count": 3,
      "total_servicios": 6,
      "cumple_meta": true,
      "faltante_para_meta": 0
    },
    {
      "id": 8,
      "name": "Martin Medina",
      "nickname": "srruth",
      "email": "jacinto.jimenez1@serviram.com.mx",
      "servicios_count": 2,
      "gm_servicios_count": 2,
      "total_servicios": 4,
      "cumple_meta": true,
      "faltante_para_meta": 0
    },
    {
      "id": 10,
      "name": "Pedro Ramos",
      "nickname": "srpedro",
      "email": "pedro.ramos@serviram.com.mx",
      "servicios_count": 2,
      "gm_servicios_count": 1,
      "total_servicios": 3,
      "cumple_meta": false,
      "faltante_para_meta": 1
    }
  ]
}
```

## Estructura de Respuesta

### Campos Principales:
- `fecha`: Fecha consultada
- `meta_diaria`: Meta establecida (4 servicios)
- `tecnicos_con_servicios`: Número de técnicos que realizaron al menos un servicio
- `tecnicos_que_cumplen`: Número de técnicos que cumplieron la meta
- `porcentaje_cumplimiento`: Porcentaje de técnicos que cumplen la meta
- `total_servicios_del_dia`: Total de servicios completados en el día
- `detalle_tecnicos`: Array con información detallada de cada técnico

### Campos por Técnico:
- `id`, `name`, `nickname`, `email`: Información básica del técnico
- `servicios_count`: Número de servicios regulares completados
- `gm_servicios_count`: Número de GM servicios completados
- `total_servicios`: Total de servicios (servicios + gm_servicios)
- `cumple_meta`: Boolean - indica si cumple la meta de 4 servicios
- `faltante_para_meta`: Servicios que faltan para cumplir la meta (0 si ya cumplió)

## Uso en Frontend (Vue/Quasar)

### Componente de Ejemplo

```vue
<template>
  <div>
    <q-card class="q-mb-md">
      <q-card-section>
        <div class="text-h6">Cumplimiento de Meta Diaria - {{ fecha }}</div>
        <q-input
          v-model="fecha"
          type="date"
          label="Fecha"
          @update:model-value="cargarCumplimiento"
        />
      </q-card-section>
    </q-card>

    <!-- Indicadores principales -->
    <div class="row q-gutter-md q-mb-md">
      <q-card class="col">
        <q-card-section class="text-center">
          <div class="text-h4 text-positive">{{ porcentajeCumplimiento }}%</div>
          <div class="text-subtitle2">Cumplimiento</div>
        </q-card-section>
      </q-card>
      
      <q-card class="col">
        <q-card-section class="text-center">
          <div class="text-h4">{{ tecnicosQueCumplen }} / {{ tecnicosConServicios }}</div>
          <div class="text-subtitle2">Técnicos que cumplen</div>
        </q-card-section>
      </q-card>
      
      <q-card class="col">
        <q-card-section class="text-center">
          <div class="text-h4 text-primary">{{ totalServiciosDelDia }}</div>
          <div class="text-subtitle2">Total servicios</div>
        </q-card-section>
      </q-card>
    </div>

    <!-- Tabla de técnicos -->
    <q-table
      :rows="detalleTecnicos"
      :columns="columnas"
      title="Detalle por Técnico"
      row-key="id"
      :pagination="{rowsPerPage: 0}"
    >
      <!-- Slot para estado de cumplimiento -->
      <template v-slot:body-cell-cumple_meta="props">
        <q-td :props="props">
          <q-badge 
            :color="props.value ? 'positive' : 'warning'"
            :label="props.value ? 'CUMPLE' : 'NO CUMPLE'"
          />
        </q-td>
      </template>
      
      <!-- Slot para faltante -->
      <template v-slot:body-cell-faltante_para_meta="props">
        <q-td :props="props">
          <span v-if="props.value > 0" class="text-warning">
            Faltan {{ props.value }}
          </span>
          <span v-else class="text-positive">
            ✓ Completo
          </span>
        </q-td>
      </template>
    </q-table>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import { api } from 'src/boot/axios'

export default {
  name: 'CumplimientoMeta',
  setup() {
    const fecha = ref(new Date().toISOString().split('T')[0])
    const porcentajeCumplimiento = ref(0)
    const tecnicosQueCumplen = ref(0)
    const tecnicosConServicios = ref(0)
    const totalServiciosDelDia = ref(0)
    const detalleTecnicos = ref([])
    
    const columnas = [
      { 
        name: 'name', 
        label: 'Técnico', 
        field: 'name', 
        align: 'left',
        sortable: true
      },
      { 
        name: 'nickname', 
        label: 'Usuario', 
        field: 'nickname', 
        align: 'left'
      },
      { 
        name: 'servicios_count', 
        label: 'Servicios', 
        field: 'servicios_count', 
        align: 'center'
      },
      { 
        name: 'gm_servicios_count', 
        label: 'GM Servicios', 
        field: 'gm_servicios_count', 
        align: 'center'
      },
      { 
        name: 'total_servicios', 
        label: 'Total', 
        field: 'total_servicios', 
        align: 'center',
        sortable: true,
        sort: (a, b) => b - a
      },
      { 
        name: 'cumple_meta', 
        label: 'Estado', 
        field: 'cumple_meta', 
        align: 'center'
      },
      { 
        name: 'faltante_para_meta', 
        label: 'Faltante', 
        field: 'faltante_para_meta', 
        align: 'center'
      }
    ]
    
    const cargarCumplimiento = async () => {
      try {
        const { data } = await api.get('/api/reportes/cumplimiento-meta', {
          params: { fecha: fecha.value }
        })
        
        porcentajeCumplimiento.value = data.porcentaje_cumplimiento
        tecnicosQueCumplen.value = data.tecnicos_que_cumplen
        tecnicosConServicios.value = data.tecnicos_con_servicios
        totalServiciosDelDia.value = data.total_servicios_del_dia
        detalleTecnicos.value = data.detalle_tecnicos
        
      } catch (error) {
        console.error('Error cargando cumplimiento:', error)
        // Manejo de errores
      }
    }
    
    onMounted(() => {
      cargarCumplimiento()
    })
    
    return {
      fecha,
      porcentajeCumplimiento,
      tecnicosQueCumplen,
      tecnicosConServicios,
      totalServiciosDelDia,
      detalleTecnicos,
      columnas,
      cargarCumplimiento
    }
  }
}
</script>
```

## Lógica de Negocio

1. **Meta Diaria:** 4 servicios completados por técnico por día
2. **Servicios Contabilizados:** Solo servicios con fecha de `salida` (completados)
3. **Tipos de Servicios:** Se suman `servicios` y `gm_servicios`
4. **Filtro:** Solo muestra técnicos que completaron al menos 1 servicio en la fecha
5. **Ordenamiento:** Por total de servicios descendente

## Consideraciones Técnicas

- **Autenticación:** Requiere `auth:sanctum`
- **Performance:** Una consulta por técnico - considera caché para grandes equipos
- **Fecha por Defecto:** Usa la fecha actual del servidor
- **Timezone:** Respeta la configuración de timezone de Laravel

## Casos de Uso

1. **Dashboard Diario:** Mostrar estado actual del cumplimiento
2. **Gestión de Equipos:** Identificar técnicos que necesitan apoyo
3. **Reportes Históricos:** Análizar cumplimiento en fechas anteriores
4. **KPIs Operativos:** Calcular métricas de productividad del equipo