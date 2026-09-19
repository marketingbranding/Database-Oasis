<?php

namespace App\Support\Database;

use Illuminate\Support\Facades\DB;

final class PartialUniqueGuard
{
    /**
     * Drivers with native partial-index support for conditional guards.
     */
    public static function supportsPartialIndexes(): bool
    {
        return in_array(DB::getDriverName(), ['pgsql', 'sqlite'], true);
    }

    public static function isMysqlFamily(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);
    }

    /**
     * Drop an index with driver-correct syntax. Missing indexes are ignored.
     */
    public static function dropIndex(string $index, string $table): void
    {
        if (self::isMysqlFamily()) {
            DB::statement("DROP INDEX IF EXISTS {$index} ON {$table}");

            return;
        }

        DB::statement("DROP INDEX IF EXISTS {$index}");
    }

    /**
     * Create a PostgreSQL/SQLite partial unique index. No-op on MySQL/MariaDB,
     * where callers provide a nullable generated column + plain unique index
     * instead (unique indexes ignore the NULL rows, reproducing partial
     * uniqueness).
     */
    public static function createPartialUnique(string $table, string $index, string $columns, string $predicate): void
    {
        if (! self::supportsPartialIndexes()) {
            return;
        }

        DB::statement("CREATE UNIQUE INDEX {$index} ON {$table} ({$columns}) WHERE {$predicate}");
    }

    /**
     * Raw nullability alteration for MySQL/MariaDB, which cannot use
     * Blueprint::change() without doctrine/dbal.
     */
    public static function modifyNullable(string $table, string $column, string $type, bool $nullable): void
    {
        $null = $nullable ? 'NULL' : 'NOT NULL';

        DB::statement("ALTER TABLE {$table} MODIFY {$column} {$type} {$null}");
    }
}
