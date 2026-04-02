<?php

namespace App\Modules\CRM\Support;

use App\Modules\CRM\Models\CrmAccount;
use App\Modules\CRM\Models\CrmContact;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmOpportunity;
use Illuminate\Database\Eloquent\Model;

class CrmSubjectRegistry
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            CrmAccount::class => __('crm.subject_types.account'),
            CrmContact::class => __('crm.subject_types.contact'),
            CrmLead::class => __('crm.subject_types.lead'),
            CrmOpportunity::class => __('crm.subject_types.opportunity'),
        ];
    }

    public static function isSupported(?string $subjectType): bool
    {
        return $subjectType !== null && array_key_exists($subjectType, self::options());
    }

    public static function typeLabel(?string $subjectType): string
    {
        if (! $subjectType) {
            return __('crm.common.unknown');
        }

        return self::options()[$subjectType] ?? __('crm.common.unknown');
    }

    /**
     * @return array<int, string>
     */
    public static function recordOptions(?string $subjectType): array
    {
        return match ($subjectType) {
            CrmAccount::class => CrmAccount::query()->orderBy('id')->get()->mapWithKeys(
                fn (CrmAccount $record): array => [$record->id => self::recordLabel($record)]
            )->all(),
            CrmContact::class => CrmContact::query()->orderBy('id')->get()->mapWithKeys(
                fn (CrmContact $record): array => [$record->id => self::recordLabel($record)]
            )->all(),
            CrmLead::class => CrmLead::query()->orderBy('id')->get()->mapWithKeys(
                fn (CrmLead $record): array => [$record->id => self::recordLabel($record)]
            )->all(),
            CrmOpportunity::class => CrmOpportunity::query()->orderBy('id')->get()->mapWithKeys(
                fn (CrmOpportunity $record): array => [$record->id => self::recordLabel($record)]
            )->all(),
            default => [],
        };
    }

    public static function recordLabel(?Model $record): string
    {
        if (! $record) {
            return '-';
        }

        return match (true) {
            $record instanceof CrmAccount => trim($record->code.' - '.$record->name),
            $record instanceof CrmContact => trim($record->name.' - '.($record->email ?? '')),
            $record instanceof CrmLead => trim($record->lead_no.' - '.$record->name),
            $record instanceof CrmOpportunity => trim($record->opportunity_no.' - '.$record->name),
            default => class_basename($record).' #'.$record->getKey(),
        };
    }

    /**
     * @return class-string<Model>|null
     */
    public static function modelClass(?string $subjectType): ?string
    {
        if (! self::isSupported($subjectType)) {
            return null;
        }

        return $subjectType;
    }
}
