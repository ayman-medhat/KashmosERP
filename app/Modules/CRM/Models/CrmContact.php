<?php

namespace App\Modules\CRM\Models;

use App\Core\Models\CompanyProfile;
use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmContact extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_profile_id',
        'crm_account_id',
        'first_name',
        'last_name',
        'job_title',
        'email',
        'phone',
        'address_translations',
        'owner_id',
        'assigned_by',
        'last_activity_at',
        'next_follow_up_at',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'address_translations' => 'array',
        'last_activity_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'is_active' => 'bool',
    ];

    public function getNameAttribute(): string
    {
        return trim(($this->first_name ?? '').' '.($this->last_name ?? ''));
    }

    public function companyProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(CrmAccount::class, 'crm_account_id');
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

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'crm_contact_id');
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(CrmOpportunity::class, 'crm_contact_id');
    }

    public function activities(): HasMany
    {
        return $this->hasMany(CrmActivity::class, 'crm_contact_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(CrmTask::class, 'crm_contact_id');
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
