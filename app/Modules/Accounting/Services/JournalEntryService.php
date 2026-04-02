<?php

namespace App\Modules\Accounting\Services;

use App\Modules\Accounting\Models\JournalEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class JournalEntryService
{
    public function create(array $attributes, array $lines): JournalEntry
    {
        $normalizedLines = $this->normalizeAndValidateLines($lines);
        [$totalDebit, $totalCredit] = $this->lineTotals($normalizedLines);
        $this->ensureBalancedTotals($totalDebit, $totalCredit);

        return DB::transaction(function () use ($attributes, $normalizedLines, $totalDebit, $totalCredit): JournalEntry {
            $entry = JournalEntry::query()->create([
                'uuid' => (string) Str::uuid(),
                'entry_no' => $attributes['entry_no'] ?? $this->nextEntryNo(),
                'entry_date' => $attributes['entry_date'] ?? now()->toDateString(),
                'status' => 'draft',
                'source_type' => $attributes['source_type'] ?? null,
                'source_id' => $attributes['source_id'] ?? null,
                'reference_no' => $attributes['reference_no'] ?? null,
                'description_translations' => $attributes['description_translations'] ?? null,
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'created_by' => auth()->id(),
            ]);

            foreach ($normalizedLines as $index => $line) {
                $entry->lines()->create([
                    'chart_of_account_id' => $line['chart_of_account_id'],
                    'line_no' => $index + 1,
                    'description_translations' => $line['description_translations'] ?? null,
                    'debit' => $line['debit'],
                    'credit' => $line['credit'],
                ]);
            }

            return $entry->refresh()->load(['lines.account', 'creator']);
        });
    }

    public function post(JournalEntry $entry): JournalEntry
    {
        return DB::transaction(function () use ($entry): JournalEntry {
            $entry = JournalEntry::query()
                ->whereKey($entry->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($entry->status !== 'draft') {
                throw new \DomainException('Only draft journal entries can be posted.');
            }

            $entry->load('lines');
            $normalizedLines = $this->normalizeAndValidateLines($entry->lines->toArray());
            [$totalDebit, $totalCredit] = $this->lineTotals($normalizedLines);
            $this->ensureBalancedTotals($totalDebit, $totalCredit);

            $entry->forceFill([
                'status' => 'posted',
                'total_debit' => $totalDebit,
                'total_credit' => $totalCredit,
                'posted_at' => now(),
            ])->save();

            return $entry->refresh()->load(['lines.account', 'creator']);
        });
    }

    /**
     * @param  array<int, array<string, mixed>>  $lines
     * @return array<int, array{chart_of_account_id: int, debit: float, credit: float, description_translations: array<string, string>|null}>
     */
    protected function normalizeAndValidateLines(array $lines): array
    {
        if (count($lines) < 2) {
            throw new \DomainException('Journal entry requires at least two lines.');
        }

        $normalized = [];

        foreach ($lines as $line) {
            $accountId = (int) ($line['chart_of_account_id'] ?? 0);
            $debit = (float) ($line['debit'] ?? 0);
            $credit = (float) ($line['credit'] ?? 0);

            if ($accountId <= 0) {
                throw new \DomainException('Each journal line must have an account.');
            }

            if ($debit < 0 || $credit < 0) {
                throw new \DomainException('Debit and credit values must be non-negative.');
            }

            $hasDebit = $debit > 0;
            $hasCredit = $credit > 0;

            if ($hasDebit === $hasCredit) {
                throw new \DomainException('Each journal line must have either debit or credit, not both.');
            }

            $normalized[] = [
                'chart_of_account_id' => $accountId,
                'debit' => round($debit, 4),
                'credit' => round($credit, 4),
                'description_translations' => $line['description_translations'] ?? null,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<int, array{debit: float, credit: float}>  $lines
     * @return array{0: float, 1: float}
     */
    protected function lineTotals(array $lines): array
    {
        $totalDebit = round(array_sum(array_map(static fn (array $line): float => $line['debit'], $lines)), 4);
        $totalCredit = round(array_sum(array_map(static fn (array $line): float => $line['credit'], $lines)), 4);

        return [$totalDebit, $totalCredit];
    }

    protected function ensureBalancedTotals(float $totalDebit, float $totalCredit): void
    {
        if ($totalDebit <= 0 || $totalCredit <= 0) {
            throw new \DomainException('Journal entry totals must be greater than zero.');
        }

        if ($totalDebit !== $totalCredit) {
            throw new \DomainException('Total debit must equal total credit.');
        }
    }

    protected function nextEntryNo(): string
    {
        $next = (int) JournalEntry::query()->count() + 1;

        return 'JE-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

