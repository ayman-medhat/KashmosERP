<?php

namespace App\Modules\CRM\Models;

use App\Core\Models\CompanyProfile;
use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmOpportunity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_profile_id',
        'opportunity_no',
        'name',
        'crm_account_id',
        'crm_contact_id',
        'crm_lead_id',
        'crm_pipeline_stage_id',
        'status',
        'probability',
        'expected_value',
        'expected_close_date',
        'won_at',
        'lost_at',
        'last_activity_at',
        'next_follow_up_at',
        'details',
        'owner_id',
        'assigned_by',
        'created_by',
    ];

    protected $casts = [
        'expected_value' => 'decimal:4',
        'expected_close_date' => 'date',
        'won_at' => 'datetime',
        'lost_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'details' => 'array',
    ];

    public function companyProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CrmAccount::class, 'crm_account_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(CrmContact::class, 'crm_contact_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(CrmLead::class, 'crm_lead_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(CrmPipelineStage::class, 'crm_pipeline_stage_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function stageHistory(): HasMany
    {
        return $this->hasMany(CrmStageHistory::class, 'crm_opportunity_id');
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'subject');
    }

    public function tasks(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(CrmTask::class, 'subject');
    }

    public function notes(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(CrmNote::class, 'subject');
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(CrmAttachment::class, 'subject');
    }

    public function emails(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(CrmEmail::class, 'subject');
    }

    public function calls(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(CrmCall::class, 'subject');
    }
}
