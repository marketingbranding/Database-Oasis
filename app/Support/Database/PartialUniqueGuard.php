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
     * Create a PostgreSQL/SQLite partial unique index.
     */
    public static function createPartialUnique(string $table, string $index, string $columns, string $predicate): void
    {
        if (! self::supportsPartialIndexes()) {
            return;
        }

        DB::statement("CREATE UNIQUE INDEX {$index} ON {$table} ({$columns}) WHERE {$predicate}");
    }

    public static function createMysqlTriggerGuard(
        string $table,
        string $index,
        string $guardColumn,
        string $guardExpression,
        ?string $additionalIndexColumn = null,
    ): void {
        if (! self::isMysqlFamily()) {
            return;
        }

        $insertTrigger = self::triggerName($table, $guardColumn, 'bi');
        $updateTrigger = self::triggerName($table, $guardColumn, 'bu');
        $indexColumns = $additionalIndexColumn === null
            ? $guardColumn
            : "{$guardColumn}, {$additionalIndexColumn}";

        DB::unprepared("CREATE TRIGGER {$insertTrigger} BEFORE INSERT ON {$table} FOR EACH ROW SET NEW.{$guardColumn} = {$guardExpression}");
        DB::unprepared("CREATE TRIGGER {$updateTrigger} BEFORE UPDATE ON {$table} FOR EACH ROW SET NEW.{$guardColumn} = {$guardExpression}");
        DB::statement("CREATE UNIQUE INDEX {$index} ON {$table} ({$indexColumns})");
    }

    public static function dropMysqlTriggerGuard(string $table, string $guardColumn): void
    {
        if (! self::isMysqlFamily()) {
            return;
        }

        DB::unprepared('DROP TRIGGER IF EXISTS '.self::triggerName($table, $guardColumn, 'bi'));
        DB::unprepared('DROP TRIGGER IF EXISTS '.self::triggerName($table, $guardColumn, 'bu'));
    }

    private static function triggerName(string $table, string $guardColumn, string $event): string
    {
        return "{$table}_{$guardColumn}_guard_{$event}";
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
