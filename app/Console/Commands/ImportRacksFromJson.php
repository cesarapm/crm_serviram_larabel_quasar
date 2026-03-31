<?php

namespace App\Console\Commands;

use App\Models\Rack;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportRacksFromJson extends Command
{
    protected $signature = 'import:racks {file? : Ruta al archivo JSON} {--force : Ejecuta sin confirmacion}';
    protected $description = 'Importa racks desde un archivo JSON exportado de Firebase';

    public function handle()
    {
        $filePath = $this->argument('file') ?? storage_path('app/imports/racks.json');
        
        if (!file_exists($filePath)) {
            $this->error("El archivo {$filePath} no existe.");
            return 1;
        }

        $this->info("Importando racks desde: {$filePath}");

        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (!$data) {
            $this->error('Error al decodificar el archivo JSON.');
            return 1;
        }

        $this->info('Archivo JSON cargado correctamente. Racks encontrados: ' . count($data));

        if (count($data) === 0) {
            $this->info('No hay racks para importar.');
            return 0;
        }

        if (!$this->option('force') && !$this->confirm('¿Deseas proceder con la importación? Esto puede sobrescribir datos existentes.')) {
            $this->info('Importación cancelada.');
            return 0;
        }

        $progressBar = $this->output->createProgressBar(count($data));
        $progressBar->start();

        $importados = 0;
        $actualizados = 0;
        $errores = 0;

        DB::beginTransaction();

        try {
            foreach ($data as $rack) {
                try {
                    $rackData = $this->mapFirebaseData($rack);
                    
                    // Buscar si ya existe por firebase_id o nombre
                    $existingRack = Rack::where('firebase_id', $rack['id'])
                        ->orWhere('nombre', $rackData['nombre'])
                        ->first();

                    if ($existingRack) {
                        $existingRack->update($rackData);
                        $actualizados++;
                    } else {
                        Rack::create($rackData);
                        $importados++;
                    }
                } catch (\Exception $e) {
                    $this->error("\nError procesando rack {$rack['id']}: " . $e->getMessage());
                    $errores++;
                }

                $progressBar->advance();
            }

            DB::commit();
            $progressBar->finish();

            $this->info("\n\nImportación completada:");
            $this->table(
                ['Resultado', 'Cantidad'],
                [
                    ['Importados', $importados],
                    ['Actualizados', $actualizados],
                    ['Errores', $errores]
                ]
            );

            // Mostrar resumen de racks importados
            if ($importados > 0 || $actualizados > 0) {
                $this->info("\nRacks en el sistema:");
                $racks = Rack::orderBy('nombre')->get(['nombre', 'descripcion', 'niveles', 'capacidad']);
                $this->table(
                    ['Nombre', 'Descripción', 'Niveles', 'Capacidad'],
                    $racks->map(function ($rack) {
                        return [
                            $rack->nombre,
                            $rack->descripcion,
                            $rack->niveles,
                            $rack->capacidad
                        ];
                    })->toArray()
                );
            }

        } catch (\Exception $e) {
            DB::rollback();
            $this->error("\nError durante la importación: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function mapFirebaseData(array $firebaseRack): array
    {
        $data = $firebaseRack['data'];
        
        return [
            'firebase_id' => $firebaseRack['id'],
            'nombre' => strtoupper(trim($data['nombre'] ?? '')),
            'descripcion' => $data['descripcion'] ?? null,
            'ubicacion' => $data['ubicacion'] ?? '',
            'niveles' => (int)($data['niveles'] ?? 1),
            'capacidad' => (int)($data['capacidad'] ?? 1),
            'posiciones_por_nivel' => (int)($data['posicionesPorNivel'] ?? 1),
            'created_at' => $this->mapFirebaseTimestamp($data['fechaCreacion'] ?? null),
            'updated_at' => $this->mapFirebaseTimestamp($data['fechaActualizacion'] ?? null),
        ];
    }

    private function mapFirebaseTimestamp(?array $timestamp): ?Carbon
    {
        if (!$timestamp || !isset($timestamp['seconds'])) {
            return null;
        }

        return Carbon::createFromTimestamp($timestamp['seconds']);
    }
}