<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\MovimientoInventario;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportMovimientosFromJson extends Command
{
    protected $signature = 'import:movimientos {file? : Ruta al archivo JSON} {--dry-run : Solo validar sin guardar}';

    protected $description = 'Importa movimientos de inventario desde un JSON exportado de Firebase';

    public function handle(): int
    {
        $filePath = $this->argument('file') ?? storage_path('app/imports/movimientos.json');
        $dryRun = (bool) $this->option('dry-run');

        if (!is_file($filePath)) {
            $this->error("No existe el archivo: {$filePath}");
            return self::FAILURE;
        }

        $rows = json_decode((string) file_get_contents($filePath), true);

        if (!is_array($rows)) {
            $this->error('El JSON no es un arreglo valido.');
            return self::FAILURE;
        }

        $total = count($rows);
        $this->info("Movimientos encontrados: {$total}");

        if ($total === 0) {
            $this->info('El archivo movimientos.json esta vacio, no hay nada que importar.');
            return self::SUCCESS;
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;

        DB::beginTransaction();

        try {
            foreach ($rows as $row) {
                $mapped = $this->mapMovimiento($row);

                if ($mapped === null) {
                    $skipped++;
                    continue;
                }

                if ($dryRun) {
                    $created++;
                    continue;
                }

                $firebaseId = $mapped['firebase_id'] ?? null;
                if (!empty($firebaseId)) {
                    $existing = MovimientoInventario::where('firebase_id', $firebaseId)->first();
                    if ($existing) {
                        $existing->update($mapped);
                        $updated++;
                        continue;
                    }
                }

                MovimientoInventario::create($mapped);
                $created++;
            }

            if ($dryRun) {
                DB::rollBack();
                $this->info("Dry-run completado. Candidatos: {$created}, Saltados: {$skipped}");
                return self::SUCCESS;
            }

            DB::commit();

            $this->info('Importacion completada.');
            $this->table(
                ['Creados', 'Actualizados', 'Saltados'],
                [[$created, $updated, $skipped]]
            );

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Error al importar movimientos: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    private function mapMovimiento(array $row): ?array
    {
        $data = $row['data'] ?? $row;

        $firebaseItemId = (string) ($data['itemFirebaseId'] ?? $data['item_firebase_id'] ?? '');
        $itemCodigo = (string) ($data['itemCodigo'] ?? $data['item_codigo'] ?? '');
        $itemIdRaw = $data['item_id'] ?? null;

        $itemId = null;

        if (!empty($itemIdRaw) && is_numeric($itemIdRaw)) {
            $item = Item::find((int) $itemIdRaw);
            $itemId = $item?->id;
        }

        if ($itemId === null && $firebaseItemId !== '') {
            $item = Item::where('firebase_id', $firebaseItemId)->first();
            $itemId = $item?->id;
        }

        if ($itemId === null && $itemCodigo !== '') {
            $item = Item::where('codigo', $itemCodigo)->first();
            $itemId = $item?->id;
        }

        if ($itemId === null) {
            return null;
        }

        $tipo = (string) ($data['tipo_movimiento'] ?? $data['tipo'] ?? 'ajuste');
        $tipo = in_array($tipo, ['entrada', 'salida', 'ajuste', 'transferencia', 'devolucion', 'perdida', 'inicial'], true)
            ? $tipo
            : 'ajuste';

        $stockAnterior = (int) ($data['stock_anterior'] ?? $data['stockAnterior'] ?? 0);
        $stockNuevo = (int) ($data['stock_nuevo'] ?? $data['stockNuevo'] ?? 0);
        $cantidad = (int) ($data['cantidad'] ?? abs($stockNuevo - $stockAnterior));

        return [
            'firebase_id' => (string) ($row['id'] ?? $data['firebase_id'] ?? ''),
            'item_id' => $itemId,
            'user_id' => null,
            'tipo_movimiento' => $tipo,
            'cantidad' => max(1, $cantidad),
            'stock_anterior' => max(0, $stockAnterior),
            'stock_nuevo' => max(0, $stockNuevo),
            'observaciones' => (string) ($data['observaciones'] ?? $data['descripcion'] ?? ''),
            'referencia_tipo' => $data['referencia_tipo'] ?? $data['referenciaTipo'] ?? null,
            'referencia_id' => $data['referencia_id'] ?? $data['referenciaId'] ?? null,
            'created_at' => $this->mapTimestamp($data['fechaCreacion'] ?? $data['created_at'] ?? null),
            'updated_at' => $this->mapTimestamp($data['fechaActualizacion'] ?? $data['updated_at'] ?? null),
        ];
    }

    private function mapTimestamp(mixed $value): ?Carbon
    {
        if (is_array($value) && isset($value['seconds'])) {
            return Carbon::createFromTimestamp((int) $value['seconds']);
        }

        if (is_string($value) && trim($value) !== '') {
            try {
                return Carbon::parse($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return null;
    }
}
