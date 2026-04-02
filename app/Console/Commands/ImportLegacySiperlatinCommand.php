<?php

namespace App\Console\Commands;

use App\InventoryAuthority;
use App\InventoryBrand;
use App\InventoryCondition;
use App\InventoryItem;
use App\InventoryItemDetail;
use App\InventoryMaintenanceTransaction;
use App\InventoryRoom;
use App\InventoryTransactionAttachment;
use App\InventoryUnit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImportLegacySiperlatinCommand extends Command
{
    protected $signature = 'inventory:import-legacy {--connection=siperlatin_legacy} {--path=}';

    protected $description = 'Import data legacy Siperlatin ke modul inventaris aplikasi utama';

    public function handle()
    {
        $connection = $this->option('connection');
        $legacyConfig = $this->resolveLegacyConfig();
        $this->configureLegacyConnection($connection, $legacyConfig);
        $legacyPath = $this->option('path')
            ?: env('SIPERLATIN_LEGACY_PATH')
            ?: ($legacyConfig['storage_path'] ?? 'C:\\xampp\\htdocs\\siperlatin\\storage');

        DB::purge($connection);
        $db = DB::connection($connection);

        foreach (['tb_barang', 'tb_detail_barang', 'tb_transaksi'] as $table) {
            if (!$db->getSchemaBuilder()->hasTable($table)) {
                $this->error('Tabel legacy tidak ditemukan: ' . $table . ' pada koneksi ' . $connection);
                return 1;
            }
        }

        $unitMap = [];
        foreach ($db->table('tb_satuan_barang')->get() as $row) {
            $unitMap[$row->id] = InventoryUnit::updateOrCreate(
                ['legacy_source_id' => $row->id],
                ['name' => $row->nama_satuan, 'description' => $row->keterangan ?? null]
            )->id;
        }

        $conditionMap = [];
        foreach ($db->table('tb_kondisi_barang')->get() as $row) {
            $conditionMap[$row->id] = InventoryCondition::updateOrCreate(
                ['legacy_source_id' => $row->id],
                ['name' => $row->nama, 'description' => null]
            )->id;
        }

        $roomMap = [];
        foreach ($db->table('tb_ruang')->get() as $row) {
            $roomMap[$row->id] = InventoryRoom::updateOrCreate(
                ['legacy_source_id' => $row->id],
                ['name' => $row->nama_ruang, 'description' => $row->keterangan ?? null]
            )->id;
        }

        $brandMap = [];
        foreach ($db->table('tb_brand')->get() as $row) {
            $brandMap[$row->id] = InventoryBrand::updateOrCreate(
                ['legacy_source_id' => $row->id],
                ['name' => $row->nama_brand, 'description' => $row->keterangan ?? null]
            )->id;
        }

        if ($db->getSchemaBuilder()->hasTable('tb_kuasa_pengguna_barang')) {
            $authority = $db->table('tb_kuasa_pengguna_barang')->first();
            if ($authority) {
                InventoryAuthority::updateOrCreate(
                    ['legacy_source_id' => $authority->id ?? 1],
                    [
                        'name' => $authority->nama,
                        'nip' => $authority->nip ?? null,
                        'position' => $authority->jabatan ?? null,
                        'notes' => $authority->keterangan ?? null,
                        'is_active' => true,
                    ]
                );
            }
        }

        $itemMap = [];
        foreach ($db->table('tb_barang')->get() as $row) {
            $item = InventoryItem::updateOrCreate(
                ['legacy_source_id' => $row->id],
                ['code' => $row->kode, 'name' => $row->nama, 'description' => $row->keterangan ?? null, 'is_active' => true]
            );
            $itemMap[$row->id] = $item->id;
        }

        $detailMap = [];
        foreach ($db->table('tb_detail_barang')->get() as $row) {
            $photoPath = $this->copyLegacyFile($legacyPath . DIRECTORY_SEPARATOR . 'foto', $row->foto ?? null, 'inventory/photos/legacy');
            $subCode = $this->resolveImportedSubCode($row);
            $inventoryItemId = $this->resolveInventoryItemId($itemMap, $row->id_barang, $row->kode, $row->nama);

            $detail = InventoryItemDetail::updateOrCreate(
                ['legacy_source_id' => $row->id],
                [
                    'inventory_item_id' => $inventoryItemId,
                    'sub_code' => $subCode,
                    'nup' => $row->nup ?? $row->kode,
                    'name' => $row->nama,
                    'acquisition_date' => $row->tgl_perolehan ?: null,
                    'acquisition_value' => $row->harga_perolehan ?: 0,
                    'inventory_unit_id' => $unitMap[$row->satuan] ?? null,
                    'inventory_condition_id' => $conditionMap[$row->id_kondisi_barang] ?? null,
                    'inventory_room_id' => $roomMap[$row->ruang] ?? null,
                    'inventory_brand_id' => $brandMap[$row->brand] ?? null,
                    'notes' => $row->keterangan,
                    'photo_path' => $photoPath,
                    'photo_original_name' => $row->foto ?: null,
                    'is_active' => true,
                    'qr_token' => (string) Str::uuid(),
                ]
            );

            $detailMap[$row->id] = $detail->id;
        }

        foreach ($db->table('tb_transaksi')->get() as $row) {
            $inventoryItemId = $itemMap[$row->id_barang] ?? optional(InventoryItemDetail::find($detailMap[$row->id_sub_barang] ?? null))->inventory_item_id;
            $transaction = InventoryMaintenanceTransaction::updateOrCreate(
                ['legacy_source_id' => $row->id],
                [
                    'inventory_item_id' => $inventoryItemId,
                    'inventory_item_detail_id' => $detailMap[$row->id_sub_barang] ?? null,
                    'transaction_date' => $row->tanggal,
                    'description' => $row->keterangan ?: 'Import legacy Siperlatin',
                    'amount' => $row->nominal ?: 0,
                    'source_type' => 'legacy_import',
                    'status' => 'imported',
                ]
            );

            $attachmentPath = $this->copyLegacyFile($legacyPath . DIRECTORY_SEPARATOR . 'files', $row->file_name ?? null, 'inventory/attachments/legacy');
            if ($attachmentPath) {
                InventoryTransactionAttachment::updateOrCreate(
                    [
                        'inventory_maintenance_transaction_id' => $transaction->id,
                        'original_name' => $row->file_name ?: ('legacy-' . $row->id),
                    ],
                    [
                        'file_path' => $attachmentPath,
                        'mime_type' => $this->guessMimeType($attachmentPath),
                        'file_size' => $this->guessFileSize($attachmentPath),
                    ]
                );
            }
        }

        $this->info('Import legacy Siperlatin selesai.');

        return 0;
    }

    protected function copyLegacyFile($basePath, $filename, $targetDir)
    {
        if (!$filename) {
            return null;
        }

        $source = rtrim($basePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($filename);
        if (!is_file($source)) {
            return null;
        }

        $targetPath = trim($targetDir, '/') . '/' . time() . '-' . Str::random(8) . '-' . basename($filename);
        Storage::disk('public')->makeDirectory(dirname($targetPath));
        copy($source, Storage::disk('public')->path($targetPath));

        return $targetPath;
    }

    protected function resolveLegacyConfig()
    {
        $legacyEnvFile = 'C:\\xampp\\htdocs\\siperlatin\\.env';
        $values = [
            'storage_path' => 'C:\\xampp\\htdocs\\siperlatin\\storage',
        ];

        if (!is_file($legacyEnvFile)) {
            return $values;
        }

        foreach (file($legacyEnvFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || strpos($line, '#') === 0 || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $values[$key] = $value;
        }

        return [
            'host' => $values['DB_HOST'] ?? '127.0.0.1',
            'port' => $values['DB_PORT'] ?? '3306',
            'database' => $values['DB_DATABASE'] ?? null,
            'username' => $values['DB_USERNAME'] ?? null,
            'password' => $values['DB_PASSWORD'] ?? null,
            'storage_path' => 'C:\\xampp\\htdocs\\siperlatin\\storage',
        ];
    }

    protected function configureLegacyConnection($connection, array $legacyConfig)
    {
        $current = Config::get('database.connections.' . $connection, []);

        if (!empty($current['database'])) {
            return;
        }

        Config::set('database.connections.' . $connection, array_merge([
            'driver' => 'mysql',
            'host' => $legacyConfig['host'] ?? '127.0.0.1',
            'port' => $legacyConfig['port'] ?? '3306',
            'database' => $legacyConfig['database'] ?? null,
            'username' => $legacyConfig['username'] ?? null,
            'password' => $legacyConfig['password'] ?? null,
            'unix_socket' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => false,
            'engine' => null,
        ], $current));
    }

    protected function guessMimeType($relativePath)
    {
        $absolutePath = Storage::disk('public')->path($relativePath);

        return is_file($absolutePath) ? mime_content_type($absolutePath) : null;
    }

    protected function guessFileSize($relativePath)
    {
        $absolutePath = Storage::disk('public')->path($relativePath);

        return is_file($absolutePath) ? filesize($absolutePath) : null;
    }

    protected function resolveImportedSubCode($row)
    {
        $baseCode = trim((string) $row->kode);
        $existingByLegacy = InventoryItemDetail::where('legacy_source_id', $row->id)->first();
        if ($existingByLegacy) {
            return $existingByLegacy->sub_code;
        }

        $existingBySubCode = InventoryItemDetail::where('sub_code', $baseCode)->first();
        if (!$existingBySubCode || (int) $existingBySubCode->legacy_source_id === (int) $row->id) {
            return $baseCode;
        }

        return $baseCode . '-L' . $row->id;
    }

    protected function resolveInventoryItemId(array &$itemMap, $legacyItemId, $legacyCode, $legacyName)
    {
        if (isset($itemMap[$legacyItemId])) {
            return $itemMap[$legacyItemId];
        }

        $baseCode = trim((string) $legacyCode);
        if (strpos($baseCode, '.') !== false) {
            $lastDot = strrpos($baseCode, '.');
            $baseCode = substr($baseCode, 0, $lastDot) ?: $baseCode;
        }

        if ($baseCode === '') {
            $baseCode = 'LEGACY-ITEM-' . $legacyItemId;
        }

        $item = InventoryItem::updateOrCreate(
            ['legacy_source_id' => $legacyItemId],
            [
                'code' => $this->resolveImportedItemCode($baseCode, $legacyItemId),
                'name' => 'Legacy - ' . trim((string) $legacyName),
                'description' => 'Parent barang dibuat otomatis dari detail barang legacy yang tidak memiliki induk valid.',
                'is_active' => true,
            ]
        );

        $itemMap[$legacyItemId] = $item->id;

        return $item->id;
    }

    protected function resolveImportedItemCode($baseCode, $legacyItemId)
    {
        $existing = InventoryItem::where('code', $baseCode)->first();
        if (!$existing || (int) $existing->legacy_source_id === (int) $legacyItemId) {
            return $baseCode;
        }

        return $baseCode . '-L' . $legacyItemId;
    }
}
