<?php

namespace App\Modules\MasterData\Models;

use App\Modules\MasterData\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
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
        'is_active',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'address_translations' => 'array',
        'is_active' => 'bool',
    ];
}
