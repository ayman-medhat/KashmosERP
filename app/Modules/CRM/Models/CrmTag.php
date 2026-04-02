<?php

namespace App\Modules\CRM\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmTag extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'color',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'bool',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

