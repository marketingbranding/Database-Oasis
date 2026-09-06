<?php

namespace App\Services;

use Illuminate\Support\Facades\Process;
use RuntimeException;

class LegacyMigrationBackupService
{
    /** @var callable(string): string|null */
    private $checkpointCreator = null;

    /** @param callable(string): string $creator */
    public function useCheckpointCreator(callable $creator): void
    {
        $this->checkpointCreator = $creator;
    }

    public function create(): string
    {
        $database = (string) config('database.connections.pgsql.database');

        if ($this->checkpointCreator !== null) {
            return ($this->checkpointCreator)($database);
        }

        if (config('database.default') !== 'pgsql') {
            throw new RuntimeException('Persistent legacy import requires PostgreSQL backup support.');
        }

        $directory = storage_path('app/private/legacy-backups');
        if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) {
            throw new RuntimeException('Backup directory could not be created.');
        }

        $path = $directory.'/'.now()->format('Ymd_His').'_'.$database.'.dump';
        $command = [
            'pg_dump',
            '--format=custom',
            '--no-owner',
            '--no-privileges',
            '--host='.(string) config('database.connections.pgsql.host'),
            '--port='.(string) config('database.connections.pgsql.port'),
            '--username='.(string) config('database.connections.pgsql.username'),
            '--file='.$path,
            $database,
        ];

        $result = Process::env(['PGPASSWORD' => (string) config('database.connections.pgsql.password')])
            ->timeout(1800)
            ->run($command);

        if (! $result->successful() || ! is_file($path) || filesize($path) === 0) {
            if (is_file($path)) {
                unlink($path);
            }
            throw new RuntimeException('Database backup failed: '.trim($result->errorOutput()));
        }

        return $path;
    }
}
