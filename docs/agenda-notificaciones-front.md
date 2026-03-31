# Agenda - Notificaciones virtuales para Frontend

## Objetivo

Este endpoint entrega notificaciones visuales de agenda para el frontend, calculadas en tiempo real.
No guarda colores en base de datos. Solo envia el estado para pintar en UI.

## Endpoint

GET /api/admin/agenda-alertas

Opcional:
- dias_maximos: rango de dias a evaluar (default 5, minimo 1, maximo 30)

Ejemplo:
GET /api/admin/agenda-alertas?dias_maximos=5

## Regla de colores

Se calcula con days_left (dias restantes desde hoy):

- days_left <= 1: red
- days_left <= 3: yellow
- days_left <= 5: green
- days_left > 5: no se incluye en esta lista
- days_left < 0: vencido, no se incluye

## Que significa "notificacion virtual"

- Es temporal (momentanea): se calcula al pedir el endpoint.
- No modifica ni persiste el campo text_color de la agenda.
- Sirve para que frontend pinte alertas sin cambiar datos historicos.

## Campos clave que recibe Frontend

Cada item incluye:

- days_left
- text_color
- textColor
- notificacion.dias_restantes
- notificacion.color
- notificacion.nivel

Niveles:
- critica: 1 dia o menos
- media: 3 dias o menos
- preventiva: 5 dias o menos

## Ejemplo de respuesta

[
  {
    "id": 120,
    "title": "Mantenimiento horno",
    "start": "2026-03-30T09:00:00.000000Z",
    "days_left": 2,
    "text_color": "yellow",
    "textColor": "yellow",
    "notificacion": {
      "dias_restantes": 2,
      "color": "yellow",
      "nivel": "media"
    }
  },
  {
    "id": 121,
    "title": "Servicio urgente",
    "start": "2026-03-29T11:00:00.000000Z",
    "days_left": 1,
    "text_color": "red",
    "textColor": "red",
    "notificacion": {
      "dias_restantes": 1,
      "color": "red",
      "nivel": "critica"
    }
  }
]

## Recomendacion de implementacion en Front

1. Consumir agenda-alertas para el panel de alertas.
2. Pintar color con prioridad:
- item.textColor
- item.text_color
3. Mostrar badge por nivel:
- critica -> rojo
- media -> amarillo
- preventiva -> verde
4. Si la respuesta viene vacia, mostrar: "Sin alertas proximas".

## Nota importante

El endpoint agenda normal (/api/admin/agenda) sigue funcionando como siempre.
Para alertas visuales usar /api/admin/agenda-alertas.
