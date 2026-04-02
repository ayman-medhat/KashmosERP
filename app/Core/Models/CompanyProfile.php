<?php

namespace App\Core\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name_translations',
    'email',
    'phone',
    'address_translations',
    'tax_number',
    'currency_code',
    'timezone',
])]
class CompanyProfile extends Model
{
    protected $casts = [
        'name_translations' => 'array',
        'address_translations' => 'array',
    ];

    public function getDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->name_translations[$locale]
            ?? $this->name_translations['en']
            ?? 'Kashmos ERP';
    }
}
