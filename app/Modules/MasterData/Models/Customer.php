<?php

namespace App\Modules\MasterData\Models;

use App\Modules\MasterData\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use HasTranslatedName;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'code',
        'name_translations',
        'email',
        'phone',
        'address_translations',
        'credit_limit',
        'is_active',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'address_translations' => 'array',
        'credit_limit' => 'decimal:4',
        'is_active' => 'bool',
    ];
}
