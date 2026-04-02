<?php

namespace App\Modules\CRM\Models;

use App\Core\Models\User;
use App\Modules\CRM\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CrmLeadSource extends Model
{
    use HasTranslatedName;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'code',
        'name_translations',
        'is_active',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'is_active' => 'bool',
        'is_system' => 'bool',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function leads(): HasMany
    {
        return $this->hasMany(CrmLead::class, 'crm_lead_source_id');
    }
}

