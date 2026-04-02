<?php

namespace App\Modules\Accounting\Models;

use App\Core\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChartOfAccount extends Model
{
    protected $table = 'chart_of_accounts';

    protected $fillable = [
        'uuid',
        'code',
        'name_translations',
        'account_type',
        'normal_balance',
        'parent_account_id',
        'is_active',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'is_active' => 'bool',
        'is_system' => 'bool',
    ];

    public function getNameAttribute(): string
    {
        $translations = $this->name_translations ?? [];
        $locale = app()->getLocale();

        return $translations[$locale]
            ?? $translations['en']
            ?? (string) (collect($translations)->first() ?? '');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_account_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_account_id');
    }

    public function journalLines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'chart_of_account_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

