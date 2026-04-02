<?php

namespace App\Core\Enums;

enum Locale: string
{
    case English = 'en';
    case Arabic = 'ar';

    public function direction(): string
    {
        return $this === self::Arabic ? 'rtl' : 'ltr';
    }

    public function label(): string
    {
        return match ($this) {
            self::English => 'English',
            self::Arabic => 'العربية',
        };
    }
}
