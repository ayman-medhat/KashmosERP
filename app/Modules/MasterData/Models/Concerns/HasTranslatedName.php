<?php

namespace App\Modules\MasterData\Models\Concerns;

trait HasTranslatedName
{
    public function getNameAttribute(): string
    {
        $locale = app()->getLocale();

        return $this->name_translations[$locale]
            ?? $this->name_translations['en']
            ?? (string) (collect($this->name_translations)->first() ?? '');
    }
}
