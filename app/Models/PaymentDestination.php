<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['method', 'holder_name', 'phone', 'active'])]
class PaymentDestination extends Model
{
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'active' => 'boolean',
        ];
    }
}
