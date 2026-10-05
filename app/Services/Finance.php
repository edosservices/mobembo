<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\DB;

class Finance
{
    public static function run(Closure $callback): mixed
    {
        return DB::transaction($callback, 3);
    }
}
