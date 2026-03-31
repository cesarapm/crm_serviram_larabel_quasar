# Comandos de Importacion - Almacen

Usa estos comandos en el mismo formato que los demas modulos:

```bash
php artisan racks:import-firebase storage/app/imports/racks.json --force

php artisan almacen-items:import-firebase storage/app/imports/almacen.json --force

php artisan movimientos:import-firebase storage/app/imports/movimientos.json
```

## Opcion en un solo comando

```bash
php artisan almacen:import-all --force
```

## Nota

- Si `movimientos.json` viene vacio (`[]`), el comando corre sin error pero no insertara registros.
- Si tus archivos tienen mayusculas en el nombre, usa exactamente ese nombre en el comando.
  Ejemplo: `storage/app/imports/Racks.json`.
