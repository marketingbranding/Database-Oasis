<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model as EloquentModel;

abstract class Model extends EloquentModel
{
    use HasUlids;
}
