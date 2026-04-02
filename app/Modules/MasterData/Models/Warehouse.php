<?php

namespace App\Modules\MasterData\Models;

use App\Modules\MasterData\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Warehouse extends Model
{
    use HasTranslatedName;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'code',
        'name_translations',
        'address_translations',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'address_translations' => 'array',
        'is_default' => 'bool',
        'is_active' => 'bool',
    ];
}
