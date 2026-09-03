<?php

namespace App\LegacyMigration;

enum MappingConfidence: string
{
    case Exact = 'EXACT';
    case High = 'HIGH';
    case Medium = 'MEDIUM';
    case Ambiguous = 'AMBIGUOUS';
    case Unresolved = 'UNRESOLVED';
}
