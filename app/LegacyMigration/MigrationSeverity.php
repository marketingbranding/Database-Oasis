<?php

namespace App\LegacyMigration;

enum MigrationSeverity: string
{
    case Warning = 'WARNING';
    case Review = 'REVIEW';
    case Blocking = 'BLOCKING';
}
