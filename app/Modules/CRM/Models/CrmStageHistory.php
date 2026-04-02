<?php

namespace App\Modules\CRM\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmStageHistory extends Model
{
    protected $table = 'crm_stage_history';

    protected $fillable = [
        'crm_opportunity_id',
        'from_crm_pipeline_stage_id',
        'to_crm_pipeline_stage_id',
        'from_probability',
        'to_probability',
        'note',
        'changed_by',
        'changed_at',
    ];

    protected $casts = [
        'changed_at' => 'datetime',
    ];

    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(CrmOpportunity::class, 'crm_opportunity_id');
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(CrmPipelineStage::class, 'from_crm_pipeline_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(CrmPipelineStage::class, 'to_crm_pipeline_stage_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}

