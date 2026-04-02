<?php

namespace App\Modules\CRM\Models;

use App\Core\Models\User;
use App\Modules\CRM\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmPipelineStage extends Model
{
    use HasTranslatedName;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'code',
        'name_translations',
        'stage_order',
        'color',
        'default_probability',
        'is_won_stage',
        'is_lost_stage',
        'is_active',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'is_won_stage' => 'bool',
        'is_lost_stage' => 'bool',
        'is_active' => 'bool',
        'is_system' => 'bool',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(CrmOpportunity::class, 'crm_pipeline_stage_id');
    }
}

