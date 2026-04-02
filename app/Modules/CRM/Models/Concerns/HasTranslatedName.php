<?php

namespace App\Modules\CRM\Models\Concerns;

trait HasTranslatedName
{
    public function getNameAttribute(): string
    {
        $translations = $this->name_translations ?? [];
        $locale = app()->getLocale();

        return $translations[$locale]
            ?? $translations['en']
            ?? (string) (collect($translations)->first() ?? '');
    }
}

