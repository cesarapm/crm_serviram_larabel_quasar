# Reportes y KPIs Operativos - SERVI-RAM

Este documento describe los reportes, KPIs y automatizaciones disponibles para el dashboard del frontend. El equipo de frontend puede modificar, agregar o quitar reportes según las necesidades del cliente.

---

## 1. Reportes Diarios

- **Servicios realizados vs programados**
  - Endpoint: `/api/reportes/servicios-diarios`
  - Descripción: Lista los servicios programados y los realizados por día.
  - Parámetros: fecha (opcional)

- **Facturación diaria**
  - Endpoint: `/api/reportes/facturacion-diaria`
  - Descripción: Suma total de facturación del día.
  - Parámetros: fecha (opcional)

- **Servicios por técnico**
  - Endpoint: `/api/reportes/servicios-por-tecnico`
  - Descripción: Servicios realizados agrupados por técnico.
  - Parámetros: fecha (opcional)

- **Tiempos muertos**
  - Endpoint: `/api/reportes/tiempos-muertos`
  - Descripción: Detecta huecos entre servicios en la agenda de cada técnico.
  - Parámetros: fecha (opcional)

---

## 2. Reportes Semanales

- **Total facturación semanal**
  - Endpoint: `/api/reportes/facturacion-semanal`
  - Descripción: Suma total de facturación de la semana.
  - Parámetros: semana (opcional)

- **Nuevos clientes**
  - Endpoint: `/api/reportes/nuevos-clientes`
  - Descripción: Lista de clientes creados en la semana.
  - Parámetros: semana (opcional)

- **Cotizaciones enviadas y cerradas**
  - Endpoint: `/api/reportes/cotizaciones-semanales`
  - Descripción: Cotizaciones enviadas y cerradas en la semana.
  - Parámetros: semana (opcional)

- **Productividad por técnico**
  - Endpoint: `/api/reportes/productividad-tecnico`
  - Descripción: Servicios realizados, facturación y eficiencia por técnico.
  - Parámetros: semana (opcional)

---

## 3. Ventas Automáticas

- **Alertas de mantenimiento**
  - Endpoint: `/api/alertas/mantenimiento`
  - Descripción: Clientes con servicios periódicos que requieren mantenimiento.
  - Parámetros: fecha (opcional)

- **Seguimiento a clientes sin servicio**
  - Endpoint: `/api/alertas/clientes-inactivos`
  - Descripción: Clientes sin servicios recientes.
  - Parámetros: meses_sin_servicio

- **Detección de fallas repetitivas**
  - Endpoint: `/api/alertas/fallas-repetitivas`
  - Descripción: Equipos/clientes con historial de fallas recurrentes.
  - Parámetros: periodo (opcional)

- **Seguimiento a cotizaciones pendientes**
  - Endpoint: `/api/alertas/cotizaciones-pendientes`
  - Descripción: Cotizaciones no cerradas.
  - Parámetros: ninguno

---

## 4. KPIs Técnicos

- **Servicios diarios**
- **Facturación diaria**
- **Eficiencia (%)**

## 5. KPIs Ventas

- **Contactos semanales**
- **Visitas**
- **Cierre (%)**
- **Contratos mensuales**

---

## 6. Personalización

- El frontend puede modificar este documento y los endpoints sugeridos.
- Los reportes pueden ser agregados, eliminados o renombrados según las necesidades del cliente.
- Para agregar un nuevo reporte, definir el endpoint, descripción y parámetros esperados.

---

## 7. Roadmap de Implementación

- Semana 1: Reportes diarios y semanales
- Semana 2: Alertas automáticas
- Semana 3: Reactivación de clientes
- Semana 4: Seguimiento de ventas

---

> **Nota:** Los endpoints son sugeridos y pueden ser adaptados por el equipo frontend/backend según la estructura final de la API.
