<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Writes the whole database as a plain .sql file (CREATE TABLE + INSERT),
 * which any MySQL tool can load back. Large files (CVs, photos) can be left out
 * to keep the download small.
 */
class Backup
{
    private const FILE_TABLE = 'stored_files';

    public static function tables(): array
    {
        $driver = DB::connection()->getDriverName();
        if ($driver === 'sqlite') {
            return collect(DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%' ORDER BY name"))
                ->pluck('name')->all();
        }
        return collect(DB::select('SHOW TABLES'))->map(fn ($r) => array_values((array) $r)[0])->all();
    }

    /** Row counts for the backup page. */
    public static function stats(): array
    {
        $out = [];
        foreach (self::tables() as $t) {
            try { $out[$t] = DB::table($t)->count(); } catch (\Throwable $e) { $out[$t] = null; }
        }
        return $out;
    }

    /** Streams the dump to php://output (used inside a streamed response). */
    public static function write(bool $withFiles, callable $emit): void
    {
        $pdo    = DB::connection()->getPdo();
        $driver = DB::connection()->getDriverName();

        $emit("-- Al-Afdal International University — database backup\n");
        $emit('-- Created: ' . now()->format('Y-m-d H:i:s') . ' (' . config('app.timezone') . ")\n");
        $emit('-- Files (CVs / photos) included: ' . ($withFiles ? 'yes' : 'no') . "\n\n");
        $emit("SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS = 0;\n\n");

        foreach (self::tables() as $table) {
            $q = '`' . str_replace('`', '``', $table) . '`';

            $emit("-- ------------------------------------------------\n-- Table {$table}\n");
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $create = (array) DB::selectOne("SHOW CREATE TABLE {$q}");
                $emit("DROP TABLE IF EXISTS {$q};\n" . (array_values($create)[1] ?? '') . ";\n\n");
            }

            if ($table === self::FILE_TABLE && !$withFiles) {
                $emit("-- rows skipped (files not included in this backup)\n\n");
                continue;
            }

            if ($table === self::FILE_TABLE) {
                // one row at a time so large files never sit in memory together
                foreach (DB::table($table)->orderBy('id')->pluck('id') as $id) {
                    $row = (array) DB::table($table)->where('id', $id)->first();
                    if ($row) {
                        $emit(self::insert($q, $row, $pdo));
                    }
                }
            } else {
                foreach (DB::table($table)->cursor() as $row) {
                    $emit(self::insert($q, (array) $row, $pdo));
                }
            }
            $emit("\n");
        }
        $emit("SET FOREIGN_KEY_CHECKS = 1;\n-- end of backup\n");
    }

    private static function insert(string $q, array $row, \PDO $pdo): string
    {
        $cols = implode(', ', array_map(fn ($c) => '`' . str_replace('`', '``', $c) . '`', array_keys($row)));
        $vals = implode(', ', array_map(fn ($v) => self::value($v, $pdo), array_values($row)));
        return "INSERT INTO {$q} ({$cols}) VALUES ({$vals});\n";
    }

    private static function value($v, \PDO $pdo): string
    {
        if ($v === null) return 'NULL';
        if (is_bool($v)) return $v ? '1' : '0';
        if (is_int($v) || is_float($v)) return (string) $v;
        if (is_resource($v)) $v = stream_get_contents($v);
        $v = (string) $v;
        if (!mb_check_encoding($v, 'UTF-8') || str_contains($v, "\0")) {
            return $v === '' ? "''" : '0x' . bin2hex($v);       // binary (file contents)
        }
        return $pdo->quote($v);
    }
}
