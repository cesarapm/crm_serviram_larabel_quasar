<?php

namespace App\Console\Commands;

use App\Models\Item;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportAlmacenFromJson extends Command
{
    protected $signature = 'import:almacen {file? : Ruta al archivo JSON} {--force : Ejecuta sin confirmacion}';
    protected $description = 'Importa items de almacén desde un archivo JSON exportado de Firebase';

    public function handle()
    {
        $filePath = $this->argument('file') ?? storage_path('app/imports/almacen.json');
        
        if (!file_exists($filePath)) {
            $this->error("El archivo {$filePath} no existe.");
            return 1;
        }

        $this->info("Importando datos desde: {$filePath}");

        $jsonContent = file_get_contents($filePath);
        $data = json_decode($jsonContent, true);

        if (!$data) {
            $this->error('Error al decodificar el archivo JSON.');
            return 1;
        }

        $this->info('Archivo JSON cargado correctamente. Items encontrados: ' . count($data));

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
            foreach ($data as $item) {
                try {
                    $itemData = $this->mapFirebaseData($item);
                    
                    // Buscar si ya existe por firebase_id o codigo
                    $existingItem = Item::where('firebase_id', $item['id'])
                        ->orWhere('codigo', $itemData['codigo'])
                        ->first();

                    if ($existingItem) {
                        $existingItem->update($itemData);
                        $actualizados++;
                    } else {
                        Item::create($itemData);
                        $importados++;
                    }
                } catch (\Exception $e) {
                    $this->error("\nError procesando item {$item['id']}: " . $e->getMessage());
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

        } catch (\Exception $e) {
            DB::rollback();
            $this->error("\nError durante la importación: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function mapFirebaseData(array $firebaseItem): array
    {
        $data = $firebaseItem['data'];
        
        return [
            'firebase_id' => $firebaseItem['id'],
            'codigo' => $data['codigo'] ?? '',
            'nombre' => $data['nombre'] ?? '',
            'tipo' => $this->mapTipo($data['tipo'] ?? ''),
            'marca' => $data['marca'] ?? null,
            'descripcion' => $data['descripcion'] ?? null,
            'stock' => (int)($data['stock'] ?? 0),
            'stock_minimo' => (int)($data['stockMinimo'] ?? 0),
            'unidad_medida' => $data['unidadMedida'] ?? 'pzas',
            'rack' => $this->cleanRack($data['rack'] ?? null),
            'ubicacion' => $data['ubicacion'] ?? null,
            'precio_unitario' => (float)($data['precioUnitario'] ?? 0),
            'proveedor' => $data['proveedor'] ?? null,
            'created_at' => $this->mapFirebaseTimestamp($data['fechaCreacion'] ?? null),
            'updated_at' => $this->mapFirebaseTimestamp($data['fechaActualizacion'] ?? null),
        ];
    }

    private function mapTipo(string $tipo): string
    {
        // Mapear tipos de Firebase a los permitidos en Laravel
        $tiposMap = [
            'Refacción' => 'Refacción',
            'Insumo' => 'Insumo', 
            'Herramienta' => 'Herramienta',
            'Material' => 'Material',
            'Otro' => 'Otro'
        ];

        return $tiposMap[$tipo] ?? 'Otro';
    }

    private function cleanRack(?string $rack): ?string
    {
        if (!$rack) {
            return null;
        }
        
        // Limpiar y normalizar rack (convertir a mayúscula)
        return strtoupper(trim($rack));
    }

    private function mapFirebaseTimestamp(?array $timestamp): ?Carbon
    {
        if (!$timestamp || !isset($timestamp['seconds'])) {
            return null;
        }

        return Carbon::createFromTimestamp($timestamp['seconds']);
    }
}