<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use SplFileInfo;

class BackupService
{
    public const DISK = 'local';
    public const DIR = 'backups';

    public function create(bool $manual = false): array
    {
        Storage::disk(self::DISK)->makeDirectory(self::DIR);

        $filename = 'nkama-backup-' . now()->format('Ymd-His') . '.sql';
        $relativePath = self::DIR . '/' . $filename;
        $absolutePath = Storage::disk(self::DISK)->path($relativePath);

        $handle = fopen($absolutePath, 'wb');
        fwrite($handle, "-- Nkama POS backup\n-- Created at: " . now()->toDateTimeString() . "\n\n");

        foreach ($this->tables() as $table) {
            fwrite($handle, "\n-- Table: {$table}\n");
            $create = DB::selectOne("SHOW CREATE TABLE `{$table}`");
            $createSql = (array) $create;
            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n" . end($createSql) . ";\n\n");

            DB::table($table)->orderByRaw('1')->chunk(500, function ($rows) use ($handle, $table) {
                foreach ($rows as $row) {
                    $values = array_map(fn ($value) => $value === null ? 'NULL' : DB::getPdo()->quote((string) $value), (array) $row);
                    $columns = array_map(fn ($column) => "`{$column}`", array_keys((array) $row));
                    fwrite($handle, 'INSERT INTO `' . $table . '` (' . implode(',', $columns) . ') VALUES (' . implode(',', $values) . ");\n");
                }
            });
        }

        fclose($handle);

        $this->prune();

        return [
            'path' => $relativePath,
            'filename' => $filename,
            'size' => filesize($absolutePath),
            'manual' => $manual,
        ];
    }

    public function list(): array
    {
        return collect(Storage::disk(self::DISK)->files(self::DIR))
            ->map(function (string $path) {
                $full = Storage::disk(self::DISK)->path($path);
                $file = new SplFileInfo($full);

                return [
                    'path' => $path,
                    'name' => basename($path),
                    'size' => $file->getSize(),
                    'created_at' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            })
            ->sortByDesc('created_at')
            ->values()
            ->all();
    }

    public function latest(): ?array
    {
        return $this->list()[0] ?? null;
    }

    private function tables(): array
    {
        return collect(DB::select('SHOW TABLES'))
            ->map(fn ($row) => array_values((array) $row)[0] ?? null)
            ->filter()
            ->values()
            ->all();
    }

    private function prune(): void
    {
        $keep = BackupSettings::keep();
        collect(Storage::disk(self::DISK)->files(self::DIR))
            ->sortByDesc(fn ($path) => filemtime(Storage::disk(self::DISK)->path($path)))
            ->slice($keep)
            ->each(fn ($path) => Storage::disk(self::DISK)->delete($path));
    }
}