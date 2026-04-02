<?php

namespace App\Modules\CRM\Models;

use App\Core\Models\CompanyProfile;
use App\Core\Models\User;
use App\Modules\CRM\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmAccount extends Model
{
    use HasTranslatedName;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_profile_id',
        'code',
        'name_translations',
        'industry',
        'website',
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
        'name_translations' => 'array',
        'address_translations' => 'array',
        'last_activity_at' => 'datetime',
        'next_follow_up_at' => 'datetime',
        'is_active' => 'bool',
    ];

    public function companyProfile(): BelongsTo
    {
        return $this->belongsTo(CompanyProfile::class);
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

    public function contacts(): HasMany
    {
        return $this->hasMany(CrmContact::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(CrmOpportunity::class);
    }

    public function activities(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'subject');
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
