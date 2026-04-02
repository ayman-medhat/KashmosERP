<?php

namespace App\Modules\CRM\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmAssignmentRule extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'entity_type',
        'priority',
        'is_active',
        'conditions',
        'assignment_strategy',
        'assigned_user_ids',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'bool',
        'conditions' => 'array',
        'assigned_user_ids' => 'array',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

