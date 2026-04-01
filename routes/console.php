<?php

use App\Models\ModuloPermiso;
use App\Models\Agenda;
use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EncuestaServicio;
use App\Models\GmServicio;
use App\Models\Orden;
use App\Models\Product;
use App\Models\Servicio;
use App\Models\TipoEquipo;
use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('users:import-firebase {file=storage/app/imports/users.json} {--dry-run}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        $this->line('Tip: coloca el JSON en storage/app/imports/users.json');
        return self::FAILURE;
    }

    $raw = file_get_contents($path);
    $rows = json_decode($raw, true);

    if (!is_array($rows)) {
        $this->error('El archivo no contiene un JSON válido (array).');
        return self::FAILURE;
    }

    Role::firstOrCreate(['name' => 'admin']);
    Role::firstOrCreate(['name' => 'asesor']);

    $created = 0;
    $updated = 0;
    $skipped = 0;

    $resolveUniqueEmail = function (string $requestedEmail, string $nickname, ?string $firebaseId, ?int $ignoreUserId = null): string {
        $email = strtolower(trim($requestedEmail));

        $exists = User::query()
            ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->where('email', $email)
            ->exists();

        if (!$exists) {
            return $email;
        }

        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '', $nickname) ?: 'user');
        $suffix = $firebaseId ? strtolower(substr((string) $firebaseId, 0, 6)) : (string) now()->timestamp;

        $candidate = "{$slug}+{$suffix}@serviram.local";
        $i = 1;

        while (User::query()
            ->when($ignoreUserId, fn ($q) => $q->where('id', '!=', $ignoreUserId))
            ->where('email', $candidate)
            ->exists()) {
            $candidate = "{$slug}+{$suffix}{$i}@serviram.local";
            $i++;
        }

        return $candidate;
    };

    foreach ($rows as $row) {
        $firebaseId = data_get($row, 'id');
        $data = data_get($row, 'data', []);

        if (!is_array($data)) {
            $skipped++;
            continue;
        }

        $nickname = trim((string) ($data['nickname'] ?? ''));
        $email = trim((string) ($data['email'] ?? ''));

        if ($nickname === '' || $email === '') {
            $skipped++;
            $this->warn("Saltado: faltan nickname/email en firebase_id={$firebaseId}");
            continue;
        }

        $attributes = [
            'firebase_id' => $firebaseId ?: null,
            'name' => (string) ($data['nombre'] ?? $nickname),
            'nickname' => $nickname,
            'email' => $email,
            'phone' => trim((string) ($data['telefono'] ?? '')) ?: null,
            'mobile' => trim((string) ($data['celular'] ?? '')) ?: null,
            'position' => trim((string) ($data['puesto'] ?? '')) ?: null,
            'activo' => (bool) ($data['activo'] ?? true),
            'Cfolio' => (int) ($data['Cfolio'] ?? 0),
            'Dfolio' => (int) ($data['Dfolio'] ?? 0),
            'lastfolio' => (int) ($data['lastfolio'] ?? 0),
        ];

        if (!empty($data['password'])) {
            $attributes['password'] = Hash::make((string) $data['password']);
        }

        $existing = null;

        if (!empty($firebaseId)) {
            $existing = User::where('firebase_id', $firebaseId)->first();
        }

        if (!$existing) {
            $existing = User::where('nickname', $nickname)->first();
        }

        $resolvedEmail = $resolveUniqueEmail($email, $nickname, $firebaseId, $existing?->id);
        if ($resolvedEmail !== $email) {
            $this->warn("Email duplicado {$email}, se usará {$resolvedEmail} para {$nickname}");
            $attributes['email'] = $resolvedEmail;
        }

        if ($dryRun) {
            $this->line(($existing ? 'UPDATE' : 'CREATE') . " {$nickname} <{$attributes['email']}>");
            continue;
        }

        if ($existing) {
            $existing->fill($attributes)->save();
            $user = $existing;
            $updated++;
        } else {
            if (!array_key_exists('password', $attributes)) {
                $attributes['password'] = Hash::make('12345678');
            }

            $user = User::create($attributes);
            $created++;
        }

        $isAdmin = (bool) ($data['admin'] ?? false);
        $user->syncRoles([$isAdmin ? 'admin' : 'asesor']);

        // Importar permisos de módulos por usuario.
        foreach (User::DEFAULT_MODULES as $module) {
            $value = $isAdmin ? true : (bool) ($data[$module] ?? false);

            ModuloPermiso::updateOrCreate(
                ['user_id' => $user->id, 'modulo' => $module],
                ['habilitado' => $value]
            );
        }
    }

    if ($dryRun) {
        $this->info('Dry-run completado. No se hicieron cambios.');
        return self::SUCCESS;
    }

    $this->info("Importación completada. Creados: {$created}, Actualizados: {$updated}, Saltados: {$skipped}");
    return self::SUCCESS;
})->purpose('Importa usuarios desde un JSON exportado de Firebase');

Artisan::command('products:import-firebase {file=storage/app/imports/products.json} {--dry-run}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        $this->line('Tip: coloca el JSON en storage/app/imports/products.json');
        return self::FAILURE;
    }

    $raw = file_get_contents($path);
    $rows = json_decode($raw, true);

    if (!is_array($rows)) {
        $this->error('El archivo no contiene un JSON válido (array).');
        return self::FAILURE;
    }

    $created = 0;
    $updated = 0;
    $skipped = 0;

    $parseDate = function ($dateStr) {
        if (empty($dateStr) || $dateStr === '') {
            return null;
        }
        try {
            return \Carbon\Carbon::createFromFormat('Y-m-d H:i', (string) $dateStr);
        } catch (\Exception $e) {
            return null;
        }
    };

    foreach ($rows as $row) {
        $firebaseId = data_get($row, 'id');
        $data = data_get($row, 'data', []);

        if (!is_array($data)) {
            $skipped++;
            continue;
        }

        $nombre = trim((string) ($data['nombre'] ?? ''));

        if ($nombre === '') {
            $skipped++;
            $this->warn("Saltado: falta nombre en firebase_id={$firebaseId}");
            continue;
        }

        $attributes = [
            'firebase_id' => $firebaseId ?: null,
            'nombre' => $nombre,
            'marca' => trim((string) ($data['marca'] ?? '')) ?: null,
            'modelo' => trim((string) ($data['modelo'] ?? '')) ?: null,
            'serie' => trim((string) ($data['serie'] ?? '')) ?: null,
            'linea' => trim((string) ($data['linea'] ?? '')) ?: null,
            'negocio' => trim((string) ($data['negocio'] ?? '')) ?: null,
            'ubicacion' => trim((string) ($data['ubicacion'] ?? '')) ?: null,
            'mantenimiento' => trim((string) ($data['mantenimiento'] ?? '')) ?: null,
            'condicion' => (int) ($data['condicion'] ?? 1),
            'ultima' => $parseDate((string) ($data['ultima'] ?? '')),
        ];

        $existing = null;

        if (!empty($firebaseId)) {
            $existing = Product::where('firebase_id', $firebaseId)->first();
        }

        // No usar nombre para identificar existentes: puede haber productos con el mismo nombre.
        // Solo se actualiza cuando coincide firebase_id.

        if ($dryRun) {
            $this->line(($existing ? 'UPDATE' : 'CREATE') . " {$nombre}");
            continue;
        }

        if ($existing) {
            $existing->fill($attributes)->save();
            $updated++;
        } else {
            Product::create($attributes);
            $created++;
        }
    }

    if ($dryRun) {
        $this->info('Dry-run completado. No se hicieron cambios.');
        return self::SUCCESS;
    }

    $this->info("Importación completada. Creados: {$created}, Actualizados: {$updated}, Saltados: {$skipped}");
    return self::SUCCESS;
})->purpose('Importa productos desde un JSON exportado de Firebase');

Artisan::command('clientes:import-firebase {file=storage/app/imports/clientes.json} {--dry-run}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        $this->line('Tip: coloca el JSON en storage/app/imports/clientes.json');
        return self::FAILURE;
    }

    $raw = file_get_contents($path);
    $rows = json_decode($raw, true);

    if (!is_array($rows)) {
        $this->error('El archivo no contiene un JSON válido (array).');
        return self::FAILURE;
    }

    $created = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        $firebaseId = data_get($row, 'id');
        $data = data_get($row, 'data', []);

        if (!is_array($data)) {
            $skipped++;
            continue;
        }

        $compania = trim((string) ($data['compania'] ?? ''));

        if ($compania === '') {
            $skipped++;
            $this->warn("Saltado: falta compania en firebase_id={$firebaseId}");
            continue;
        }

        $attributes = [
            'firebase_id' => $firebaseId ?: null,
            'compania' => $compania,
            'contacto' => trim((string) ($data['contacto'] ?? '')) ?: null,
            'responsable' => trim((string) ($data['responsable'] ?? '')) ?: null,
            'telefono' => trim((string) ($data['telefono'] ?? '')) ?: null,
            'email' => trim((string) ($data['email'] ?? '')) ?: null,
            'ciudad' => trim((string) ($data['ciudad'] ?? '')) ?: null,
            'direccion' => trim((string) ($data['direccion'] ?? '')) ?: null,
        ];

        if ($dryRun) {
            $this->line("CREATE {$compania}");
            continue;
        }

        Cliente::create($attributes);
        $created++;
    }

    if ($dryRun) {
        $this->info('Dry-run completado. No se hicieron cambios.');
        return self::SUCCESS;
    }

    $this->info("Importación completada. Creados: {$created}, Saltados: {$skipped}");
    return self::SUCCESS;
})->purpose('Importa clientes desde un JSON exportado de Firebase');

Artisan::command('tipoequipos:import-firebase {file=storage/app/imports/tipoequipos.json} {--dry-run}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        $this->line('Tip: coloca el JSON en storage/app/imports/tipoequipos.json');
        return self::FAILURE;
    }

    $raw = file_get_contents($path);
    $rows = json_decode($raw, true);

    if (!is_array($rows)) {
        $this->error('El archivo no contiene un JSON válido (array).');
        return self::FAILURE;
    }

    $created = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        $firebaseId = data_get($row, 'id');
        $data = data_get($row, 'data', []);

        if (!is_array($data)) {
            $skipped++;
            continue;
        }

        $name = trim((string) ($data['name'] ?? ''));
        $mantenimiento = $data['mantenimiento'] ?? [];

        if ($name === '') {
            $skipped++;
            $this->warn("Saltado: falta name en firebase_id={$firebaseId}");
            continue;
        }

        $attributes = [
            'firebase_id' => $firebaseId ?: null,
            'name' => $name,
            'mantenimiento' => is_array($mantenimiento) ? $mantenimiento : [],
        ];

        if ($dryRun) {
            $this->line("CREATE {$name}");
            continue;
        }

        TipoEquipo::create($attributes);
        $created++;
    }

    if ($dryRun) {
        $this->info('Dry-run completado. No se hicieron cambios.');
        return self::SUCCESS;
    }

    $this->info("Importación completada. Creados: {$created}, Saltados: {$skipped}");
    return self::SUCCESS;
})->purpose('Importa tipos de equipo desde un JSON exportado de Firebase');

Artisan::command('servicios:import-firebase {file=storage/app/imports/servicios.json} {--dry-run}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        $this->line('Tip: coloca el JSON en storage/app/imports/servicios.json');
        return self::FAILURE;
    }

    $raw = file_get_contents($path);
    $rows = json_decode($raw, true);

    if (!is_array($rows)) {
        $this->error('El archivo no contiene un JSON válido (array).');
        return self::FAILURE;
    }

    $created = 0;
    $skipped = 0;

    $parseDate = function ($dateData) {
        if (empty($dateData) || !is_array($dateData)) {
            return null;
        }
        try {
            $seconds = $dateData['seconds'] ?? 0;
            return \Carbon\Carbon::createFromTimestamp($seconds);
        } catch (\Exception $e) {
            return null;
        }
    };

    foreach ($rows as $row) {
        $firebaseId = data_get($row, 'id');
        $data = data_get($row, 'data', []);

        if (!is_array($data)) {
            $skipped++;
            continue;
        }

        // Buscar usuario por firebase_id
        $usuarioId = null;
        if (!empty($data['usuario']['id'])) {
            $usuario = User::where('firebase_id', $data['usuario']['id'])->first();
            $usuarioId = $usuario?->id;
        }

        // Buscar equipo por firebase_id
        $equipoId = null;
        if (!empty($data['equipo']['id'])) {
            $equipo = Product::where('firebase_id', $data['equipo']['id'])->first();
            $equipoId = $equipo?->id;
        }

        $attributes = [
            'firebase_id' => $firebaseId ?: null,
            'usuario_id' => $usuarioId,
            'equipo_id' => $equipoId,
            'cliente_data' => $data['cliente'] ?? null,
            'status' => trim((string) ($data['status'] ?? 'Abierto')),
            'autorizacion' => trim((string) ($data['autorizacion'] ?? '')) ?: null,
            'servicio' => trim((string) ($data['servicio'] ?? '')) ?: null,
            'mantenimiento' => trim((string) ($data['mantenimiento'] ?? '')) ?: null,
            'condicion' => trim((string) ($data['condicion'] ?? '')) ?: null,
            'actividad' => trim((string) ($data['actividad'] ?? '')) ?: null,
            'folio' => trim((string) ($data['folio'] ?? '')) ?: null,
            'Nfolio' => trim((string) ($data['Nfolio'] ?? '')) ?: null,
            'visita' => trim((string) ($data['visita'] ?? '')) ?: null,
            'tipo' => trim((string) ($data['tipo'] ?? '')) ?: null,
            'salida' => $parseDate($data['salida'] ?? null),
            'fechas' => $data['fechas'] ?? null,
            'conceptos' => $data['conceptos'] ?? [],
            'ciclos' => $data['ciclos'] ?? [],
            'frio' => $data['frio'] ?? [],
            'tipoactividades' => $data['tipoactividades'] ?? [],
            'boton_deshabilitado' => (bool) ($data['botonDeshabilitado'] ?? false),
            'procesando_accion' => (bool) ($data['procesandoAccion'] ?? false),
        ];

        if ($dryRun) {
            $identificador = $attributes['Nfolio'] ?: $attributes['folio'] ?: "Sin folio";
            $this->line("CREATE {$identificador}");
            continue;
        }

        Servicio::create($attributes);
        $created++;
    }

    if ($dryRun) {
        $this->info('Dry-run completado. No se hicieron cambios.');
        return self::SUCCESS;
    }

    $this->info("Importación completada. Creados: {$created}, Saltados: {$skipped}");
    return self::SUCCESS;
})->purpose('Importa servicios desde un JSON exportado de Firebase');

Artisan::command('gmservicios:import-firebase {file=storage/app/imports/GmServicios.json} {--dry-run}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        $this->line('Tip: coloca el JSON en storage/app/imports/GmServicios.json');
        return self::FAILURE;
    }

    $raw = file_get_contents($path);
    $rows = json_decode($raw, true);

    if (!is_array($rows)) {
        $this->error('El archivo no contiene un JSON válido (array).');
        return self::FAILURE;
    }

    $created = 0;
    $skipped = 0;

    $parseDate = function ($dateData) {
        if (empty($dateData) || !is_array($dateData)) {
            return null;
        }

        try {
            $seconds = $dateData['seconds'] ?? 0;
            return \Carbon\Carbon::createFromTimestamp($seconds);
        } catch (\Exception $e) {
            return null;
        }
    };

    foreach ($rows as $row) {
        $firebaseId = data_get($row, 'id');
        $data = data_get($row, 'data', []);

        if (!is_array($data)) {
            $skipped++;
            continue;
        }

        $usuarioId = null;
        if (!empty($data['usuario']['id'])) {
            $usuario = User::where('firebase_id', $data['usuario']['id'])->first();
            $usuarioId = $usuario?->id;
        }

        $equipoId = null;
        if (!empty($data['equipo']['id'])) {
            $equipo = Product::where('firebase_id', $data['equipo']['id'])->first();
            $equipoId = $equipo?->id;
        }

        $attributes = [
            'firebase_id' => $firebaseId ?: null,
            'usuario_id' => $usuarioId,
            'equipo_id' => $equipoId,
            'cliente_data' => $data['cliente'] ?? null,
            'status' => trim((string) ($data['status'] ?? 'Abierto')),
            'autorizacion' => trim((string) ($data['autorizacion'] ?? '')) ?: null,
            'servicio' => trim((string) ($data['servicio'] ?? '')) ?: null,
            'mantenimiento' => trim((string) ($data['mantenimiento'] ?? '')) ?: null,
            'condicion' => trim((string) ($data['condicion'] ?? '')) ?: null,
            'actividad' => trim((string) ($data['actividad'] ?? '')) ?: null,
            'folio' => trim((string) ($data['folio'] ?? '')) ?: null,
            'Nfolio' => trim((string) ($data['Nfolio'] ?? '')) ?: null,
            'visita' => trim((string) ($data['visita'] ?? '')) ?: null,
            'tipo' => trim((string) ($data['tipo'] ?? '')) ?: null,
            'salida' => $parseDate($data['salida'] ?? null),
            'fechas' => $data['fechas'] ?? null,
            'conceptos' => $data['conceptos'] ?? [],
            'ciclos' => $data['ciclos'] ?? [],
            'frio' => $data['frio'] ?? [],
            'tipoactividades' => $data['tipoactividades'] ?? [],
            'boton_deshabilitado' => (bool) ($data['botonDeshabilitado'] ?? false),
            'procesando_accion' => (bool) ($data['procesandoAccion'] ?? false),
        ];

        if ($dryRun) {
            $identificador = $attributes['Nfolio'] ?: $attributes['folio'] ?: 'Sin folio';
            $this->line("CREATE {$identificador}");
            continue;
        }

        GmServicio::create($attributes);
        $created++;
    }

    if ($dryRun) {
        $this->info('Dry-run completado. No se hicieron cambios.');
        return self::SUCCESS;
    }

    $this->info("Importación completada. Creados: {$created}, Saltados: {$skipped}");
    return self::SUCCESS;
})->purpose('Importa servicios GM desde un JSON exportado de Firebase');

Artisan::command('encservicios:import-firebase {file=storage/app/imports/Encservicios.json} {--dry-run}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        $this->line('Tip: coloca el JSON en storage/app/imports/Encservicios.json');
        return self::FAILURE;
    }

    $raw = file_get_contents($path);
    $rows = json_decode($raw, true);

    if (!is_array($rows)) {
        $this->error('El archivo no contiene un JSON válido (array).');
        return self::FAILURE;
    }

    $created = 0;
    $updated = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        $firebaseId = data_get($row, 'id');
        $data = data_get($row, 'data', []);

        if (!is_array($data)) {
            $skipped++;
            continue;
        }

        $servicioFirebaseId = trim((string) ($data['id_servicio'] ?? ''));
        if ($servicioFirebaseId === '') {
            $skipped++;
            $this->warn("Saltado: falta id_servicio en firebase_id={$firebaseId}");
            continue;
        }

        $servicio = Servicio::query()->where('firebase_id', $servicioFirebaseId)->first();
        $gmServicio = GmServicio::query()->where('firebase_id', $servicioFirebaseId)->first();

        if ($servicio && $gmServicio) {
            $skipped++;
            $this->warn("Ambiguo: id_servicio={$servicioFirebaseId} existe en servicios y gm_servicios. Saltado.");
            continue;
        }

        if (!$servicio && !$gmServicio) {
            $skipped++;
            $this->warn("Sin referencia: id_servicio={$servicioFirebaseId} no existe en servicios ni gm_servicios.");
            continue;
        }

        $origen = $servicio ? 'servicio' : 'gm_servicio';
        $fecha = null;
        if (!empty($data['fecha'])) {
            try {
                $fecha = \Carbon\Carbon::parse((string) $data['fecha']);
            } catch (\Exception $e) {
                $fecha = null;
            }
        }

        $attributes = [
            'firebase_id' => $firebaseId ?: null,
            'origen' => $origen,
            'servicio_firebase_id' => $servicioFirebaseId,
            'servicio_id' => $servicio?->id,
            'gm_servicio_id' => $gmServicio?->id,
            'calificacion' => is_numeric($data['calificacion'] ?? null) ? (float) $data['calificacion'] : null,
            'fecha' => $fecha,
        ];

        if ($dryRun) {
            $this->line("UPSERT {$origen} {$servicioFirebaseId}");
            continue;
        }

        if (!empty($firebaseId)) {
            $record = EncuestaServicio::query()->where('firebase_id', $firebaseId)->first();

            if ($record) {
                $record->fill($attributes)->save();
                $updated++;
            } else {
                EncuestaServicio::create($attributes);
                $created++;
            }
        } else {
            EncuestaServicio::create($attributes);
            $created++;
        }
    }

    if ($dryRun) {
        $this->info('Dry-run completado. No se hicieron cambios.');
        return self::SUCCESS;
    }

    $this->info("Importación completada. Creados: {$created}, Actualizados: {$updated}, Saltados: {$skipped}");
    return self::SUCCESS;
})->purpose('Importa encuestas de servicios desde JSON y resuelve origen entre servicios y gm_servicios');

Artisan::command('cotizaciones:import-firebase {file=storage/app/imports/cotizaciones.json} {--dry-run}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        $this->line('Tip: coloca el JSON en storage/app/imports/cotizaciones.json');
        return self::FAILURE;
    }

    $raw = file_get_contents($path);
    $rows = json_decode($raw, true);

    if (!is_array($rows)) {
        $this->error('El archivo no contiene un JSON válido (array).');
        return self::FAILURE;
    }

    $created = 0;
    $skipped = 0;

    $parseDate = function ($dateData) {
        if (empty($dateData) || !is_array($dateData)) {
            return null;
        }
        try {
            $seconds = $dateData['seconds'] ?? 0;
            return \Carbon\Carbon::createFromTimestamp($seconds);
        } catch (\Exception $e) {
            return null;
        }
    };

    foreach ($rows as $row) {
        $firebaseId = data_get($row, 'id');
        $data = data_get($row, 'data', []);

        if (!is_array($data)) {
            $skipped++;
            continue;
        }

        $usuarioData = is_array($data['usuario'] ?? null) ? $data['usuario'] : null;

        // Relación por id de Firebase del usuario embebido en la cotización.
        $usuarioId = null;
        if (!empty($usuarioData['id'])) {
            $usuario = User::where('firebase_id', $usuarioData['id'])->first();
            $usuarioId = $usuario?->id;
        }

        $attributes = [
            'firebase_id' => $firebaseId ?: null,
            'usuario_id' => $usuarioId,
            'usuario_data' => $usuarioData,
            'contacto' => trim((string) ($data['contacto'] ?? '')) ?: null,
            'ciudad' => trim((string) ($data['ciudad'] ?? '')) ?: null,
            'terminos' => trim((string) ($data['terminos'] ?? '')) ?: null,
            'pago' => trim((string) ($data['pago'] ?? '')) ?: null,
            'folio_servicio' => trim((string) ($data['folio_servicio'] ?? '')) ?: null,
            'telefono' => trim((string) ($data['telefono'] ?? '')) ?: null,
            'conceptos' => is_array($data['conceptos'] ?? null) ? $data['conceptos'] : [],
            'tiempo' => trim((string) ($data['tiempo'] ?? '')) ?: null,
            'fechas' => is_array($data['fechas'] ?? null) ? $data['fechas'] : null,
            'salida' => $parseDate($data['salida'] ?? null),
            'compania' => trim((string) ($data['compania'] ?? '')) ?: null,
            'folio' => trim((string) ($data['folio'] ?? '')) ?: null,
            'direccion' => trim((string) ($data['direccion'] ?? '')) ?: null,
            'Nfolio' => trim((string) ($data['Nfolio'] ?? '')) ?: null,
            'area' => trim((string) ($data['area'] ?? '')) ?: null,
            'trabajo' => trim((string) ($data['trabajo'] ?? '')) ?: null,
            'moneda' => trim((string) ($data['moneda'] ?? '')) ?: null,
            'boton_deshabilitado' => (bool) ($data['botonDeshabilitado'] ?? false),
            'procesando_accion' => (bool) ($data['procesandoAccion'] ?? false),
        ];

        if ($dryRun) {
            $identificador = $attributes['folio'] ?: $attributes['Nfolio'] ?: 'Sin folio';
            $usuarioInfo = $usuarioId ? "user_id={$usuarioId}" : 'user_id=NULL';
            $this->line("CREATE {$identificador} ({$usuarioInfo})");
            continue;
        }

        Cotizacion::create($attributes);
        $created++;
    }

    if ($dryRun) {
        $this->info('Dry-run completado. No se hicieron cambios.');
        return self::SUCCESS;
    }

    $this->info("Importación completada. Creados: {$created}, Saltados: {$skipped}");
    return self::SUCCESS;
})->purpose('Importa cotizaciones desde un JSON exportado de Firebase');

Artisan::command('ordenes:import-firebase {file=storage/app/imports/Orden.json} {--dry-run}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        return self::FAILURE;
    }

    $rows = json_decode((string) file_get_contents($path), true);
    if (!is_array($rows)) {
        $this->error('El archivo no contiene un JSON válido (array).');
        return self::FAILURE;
    }

    $created = 0;
    $skipped = 0;

    $resolveUsuario = function ($usuarioData): array {
        if (!is_array($usuarioData)) {
            return [];
        }

        // A veces viene como objeto usuario normal (un solo usuario)
        if (!empty($usuarioData['id']) && isset($usuarioData['email'])) {
            return [$usuarioData];
        }

        // Otra veces como array indexado 0, 1, 2... (múltiples técnicos)
        $usuarios = [];
        foreach ($usuarioData as $value) {
            if (is_array($value) && !empty($value['id'])) {
                $usuarios[] = $value;
            }
        }

        return $usuarios;
    };

    foreach ($rows as $row) {
        $firebaseId = data_get($row, 'id');
        $data = data_get($row, 'data', []);

        if (!is_array($data)) {
            $skipped++;
            continue;
        }

        $usuariosData = $resolveUsuario($data['usuario'] ?? null);
        $usuarioId = null;
        
        // Usa el primer técnico como usuario_id principal (responsable)
        if (!empty($usuariosData) && !empty($usuariosData[0]['id'])) {
            $usuarioId = User::where('firebase_id', $usuariosData[0]['id'])->value('id');
        }

        $attributes = [
            'firebase_id' => $firebaseId ?: null,
            'usuario_id' => $usuarioId,
            'usuario_data' => $usuariosData,  // Ahora guarda TODOS los técnicos
            'cliente_data' => is_array($data['cliente'] ?? null) ? $data['cliente'] : null,
            'fecha' => trim((string) ($data['fecha'] ?? '')) ?: null,
            'fechas' => is_array($data['fechas'] ?? null) ? $data['fechas'] : null,
            'servicio' => trim((string) ($data['servicio'] ?? '')) ?: null,
            'mantenimiento' => trim((string) ($data['mantenimiento'] ?? '')) ?: null,
            'folio' => trim((string) ($data['folio'] ?? '')) ?: null,
            'idfolio' => trim((string) ($data['Idfolio'] ?? '')) ?: null,
            'estatus' => (bool) ($data['estatus'] ?? false),
            'equipos' => is_array($data['equipo'] ?? null) ? $data['equipo'] : (is_array($data['equipos'] ?? null) ? $data['equipos'] : []),
            'boton_deshabilitado' => (bool) ($data['botonDeshabilitado'] ?? false),
            'procesando_accion' => (bool) ($data['procesandoAccion'] ?? false),
        ];

        if ($dryRun) {
            $identificador = $attributes['idfolio'] ?: $attributes['folio'] ?: 'Sin folio';
            $tecnicos = implode(', ', array_map(
                fn($u) => $u['nickname'] ?? $u['nombre'] ?? 'Desconocido',
                $usuariosData
            )) ?: 'Sin técnico';
            $this->line("CREATE {$identificador} → Técnicos: {$tecnicos}");
            continue;
        }

        Orden::create($attributes);
        $created++;
    }

    if ($dryRun) {
        $this->info('Dry-run completado. No se hicieron cambios.');
        return self::SUCCESS;
    }

    $this->info("Importación completada. Creados: {$created}, Saltados: {$skipped}");
    return self::SUCCESS;
})->purpose('Importa ordenes desde un JSON exportado de Firebase');

Artisan::command('agenda:import-firebase {file=storage/app/imports/agenda.json} {--dry-run} {--last= : Importa solo los ultimos N registros}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');
    $last = $this->option('last');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        return self::FAILURE;
    }

    $rows = json_decode((string) file_get_contents($path), true);
    if (!is_array($rows)) {
        $this->error('El archivo no contiene un JSON válido (array).');
        return self::FAILURE;
    }

    if ($last !== null && $last !== '') {
        $limit = (int) $last;
        if ($limit <= 0) {
            $this->error('La opcion --last debe ser un entero mayor a 0.');
            return self::FAILURE;
        }

        $rows = array_slice($rows, -$limit);
        $this->info('Importando solo los ultimos ' . $limit . ' registros de agenda.');
    }

    $created = 0;
    $skipped = 0;

    foreach ($rows as $row) {
        $firebaseId = data_get($row, 'id');
        $data = data_get($row, 'data', []);

        if (!is_array($data)) {
            $skipped++;
            continue;
        }

        $idOrdenFirebase = trim((string) ($data['id_orden'] ?? '')) ?: null;
        $ordenId = null;
        if (!empty($idOrdenFirebase)) {
            $ordenId = Orden::where('firebase_id', $idOrdenFirebase)->value('id');
        }

        $startRaw = trim((string) ($data['start'] ?? '')) ?: null;
        $startParsed = null;
        if (!empty($startRaw)) {
            try {
                $startParsed = \Carbon\Carbon::parse($startRaw);
            } catch (\Exception $e) {
                $startParsed = null;
            }
        }

        $attributes = [
            'firebase_id' => $firebaseId ?: null,
            'orden_id' => $ordenId,
            'id_orden_firebase' => $idOrdenFirebase,
            'start' => $startParsed,
            'start_raw' => $startRaw,
            'fecha' => trim((string) ($data['fecha'] ?? '')) ?: null,
            'all_day' => (bool) ($data['allDay'] ?? false),
            'text_color' => trim((string) ($data['textColor'] ?? '')) ?: null,
            'title' => trim((string) ($data['title'] ?? '')) ?: null,
            'equipo_data' => is_array($data['equipo'] ?? null) ? $data['equipo'] : null,
            'block' => (bool) ($data['block'] ?? false),
            'estatus' => (bool) ($data['estatus'] ?? false),
        ];

        if ($dryRun) {
            $identificador = $attributes['title'] ?: 'Sin titulo';
            $this->line("CREATE {$identificador} (orden_id=" . ($ordenId ?? 'NULL') . ")");
            continue;
        }

        Agenda::create($attributes);
        $created++;
    }

    if ($dryRun) {
        $this->info('Dry-run completado. No se hicieron cambios.');
        return self::SUCCESS;
    }

    $this->info("Importación completada. Creados: {$created}, Saltados: {$skipped}");
    return self::SUCCESS;
})->purpose('Importa agenda desde un JSON exportado de Firebase (opcional: ultimos N con --last)');

// ============================================================================
// COMANDOS DE ALMACÉN
// ============================================================================

Artisan::command('almacen:import-items {file=storage/app/imports/almacen.json} {--dry-run} {--force}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        $this->line('Tip: coloca el JSON en storage/app/imports/almacen.json');
        return self::FAILURE;
    }

    $args = ['file' => $path];
    if ($dryRun) {
        $this->warn('El comando import:almacen no soporta --dry-run; se ejecutara importacion real.');
    }
    if ((bool) $this->option('force')) {
        $args['--force'] = true;
    }

    $this->call('import:almacen', $args);
    return self::SUCCESS;
})->purpose('Importa items de almacén desde un JSON exportado de Firebase');

Artisan::command('almacen:import-racks {file=storage/app/imports/racks.json} {--dry-run} {--force}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        $this->line('Tip: coloca el JSON en storage/app/imports/racks.json');
        return self::FAILURE;
    }

    $args = ['file' => $path];
    if ($dryRun) {
        $this->warn('El comando import:racks no soporta --dry-run; se ejecutara importacion real.');
    }
    if ((bool) $this->option('force')) {
        $args['--force'] = true;
    }

    $this->call('import:racks', $args);
    return self::SUCCESS;
})->purpose('Importa racks desde un JSON exportado de Firebase');

Artisan::command('almacen:import-movimientos {file=storage/app/imports/movimientos.json} {--dry-run}', function () {
    $fileArg = (string) $this->argument('file');
    $dryRun = (bool) $this->option('dry-run');

    $path = str_starts_with($fileArg, '/') ? $fileArg : base_path($fileArg);

    if (!is_file($path)) {
        $this->error("No existe el archivo: {$path}");
        $this->line('Tip: coloca el JSON en storage/app/imports/movimientos.json');
        return self::FAILURE;
    }

    $args = ['file' => $path];
    if ($dryRun) {
        $args['--dry-run'] = true;
    }

    $this->call('import:movimientos', $args);
    return self::SUCCESS;
})->purpose('Importa movimientos de inventario desde un JSON exportado de Firebase');

// Alias con formato legado "modulo:import-firebase"
Artisan::command('racks:import-firebase {file=storage/app/imports/racks.json} {--force}', function () {
    $args = ['file' => (string) $this->argument('file')];
    if ((bool) $this->option('force')) {
        $args['--force'] = true;
    }
    return $this->call('almacen:import-racks', $args);
})->purpose('Alias legado: importa racks desde JSON (formato modulo:import-firebase)');

Artisan::command('almacen-items:import-firebase {file=storage/app/imports/almacen.json} {--force}', function () {
    $args = ['file' => (string) $this->argument('file')];
    if ((bool) $this->option('force')) {
        $args['--force'] = true;
    }
    return $this->call('almacen:import-items', $args);
})->purpose('Alias legado: importa items de almacén desde JSON (formato modulo:import-firebase)');

Artisan::command('movimientos:import-firebase {file=storage/app/imports/movimientos.json} {--dry-run}', function () {
    $args = ['file' => (string) $this->argument('file')];
    if ((bool) $this->option('dry-run')) {
        $args['--dry-run'] = true;
    }
    return $this->call('almacen:import-movimientos', $args);
})->purpose('Alias legado: importa movimientos desde JSON (formato modulo:import-firebase)');

Artisan::command('almacen:import-all {--force}', function () {
    $this->info('=== IMPORTACIÓN COMPLETA DE ALMACÉN ===');
    
    // 1. Importar racks primero
    $this->info("\n1. Importando racks...");
    if (file_exists(storage_path('app/imports/racks.json'))) {
        $argsRacks = [];
        if ((bool) $this->option('force')) {
            $argsRacks['--force'] = true;
        }
        $this->call('almacen:import-racks', $argsRacks);
    } else {
        $this->warn('No se encontró storage/app/imports/racks.json');
    }
    
    // 2. Importar items
    $this->info("\n2. Importando items...");
    if (file_exists(storage_path('app/imports/almacen.json'))) {
        $argsItems = [];
        if ((bool) $this->option('force')) {
            $argsItems['--force'] = true;
        }
        $this->call('almacen:import-items', $argsItems);
    } else {
        $this->warn('No se encontró storage/app/imports/almacen.json');
    }
    
    // 3. Importar movimientos
    $this->info("\n3. Importando movimientos...");
    if (file_exists(storage_path('app/imports/movimientos.json'))) {
        $this->call('almacen:import-movimientos');
    } else {
        $this->warn('No se encontró storage/app/imports/movimientos.json');
    }

    // 4. Mostrar resumen
    $this->info("\n=== RESUMEN DEL SISTEMA DE ALMACÉN ===");
    $totalRacks = \App\Models\Rack::count();
    $totalItems = \App\Models\Item::count();
    $totalMovimientos = \App\Models\MovimientoInventario::count();
    
    $this->table(['Módulo', 'Registros'], [
        ['Racks', $totalRacks],
        ['Items', $totalItems],
        ['Movimientos', $totalMovimientos],
    ]);
    
    if ($totalRacks > 0 && $totalItems > 0) {
        $this->info("\n✅ Sistema de almacén listo para usar!");
        $this->line("🔗 API Endpoints disponibles:");
        $this->line("   • GET /api/admin/racks - Gestión de racks");
        $this->line("   • GET /api/admin/items - Gestión de items");
        $this->line("   • GET /api/admin/items-estadisticas - Dashboard del almacén");
    }
    
    return self::SUCCESS;
})->purpose('Importa todos los datos del sistema de almacén (racks + items + movimientos)');
