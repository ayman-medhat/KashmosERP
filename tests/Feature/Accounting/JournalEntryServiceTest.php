<?php

namespace Tests\Feature\Accounting;

use App\Modules\Accounting\Models\ChartOfAccount;
use App\Modules\Accounting\Services\JournalEntryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalEntryServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_balanced_draft_journal_entry(): void
    {
        $this->seed();

        $cash = ChartOfAccount::query()->where('code', '1000')->firstOrFail();
        $sales = ChartOfAccount::query()->where('code', '4000')->firstOrFail();

        $entry = app(JournalEntryService::class)->create([
            'entry_date' => now()->toDateString(),
            'reference_no' => 'INV-001',
            'description_translations' => [
                'en' => 'Invoice posting',
                'ar' => 'ترحيل فاتورة',
            ],
        ], [
            [
                'chart_of_account_id' => $cash->id,
                'debit' => 150,
                'credit' => 0,
            ],
            [
                'chart_of_account_id' => $sales->id,
                'debit' => 0,
                'credit' => 150,
            ],
        ]);

        $this->assertSame('draft', $entry->status);
        $this->assertSame('150.0000', $entry->total_debit);
        $this->assertSame('150.0000', $entry->total_credit);
        $this->assertCount(2, $entry->lines);
    }

    public function test_it_rejects_unbalanced_journal_entries(): void
    {
        $this->seed();

        $cash = ChartOfAccount::query()->where('code', '1000')->firstOrFail();
        $sales = ChartOfAccount::query()->where('code', '4000')->firstOrFail();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Total debit must equal total credit.');

        app(JournalEntryService::class)->create([
            'entry_date' => now()->toDateString(),
        ], [
            [
                'chart_of_account_id' => $cash->id,
                'debit' => 150,
                'credit' => 0,
            ],
            [
                'chart_of_account_id' => $sales->id,
                'debit' => 0,
                'credit' => 120,
            ],
        ]);
    }

    public function test_it_posts_a_draft_journal_entry(): void
    {
        $this->seed();

        $cash = ChartOfAccount::query()->where('code', '1000')->firstOrFail();
        $sales = ChartOfAccount::query()->where('code', '4000')->firstOrFail();
        $service = app(JournalEntryService::class);

        $draft = $service->create([
            'entry_date' => now()->toDateString(),
        ], [
            ['chart_of_account_id' => $cash->id, 'debit' => 90, 'credit' => 0],
            ['chart_of_account_id' => $sales->id, 'debit' => 0, 'credit' => 90],
        ]);

        $posted = $service->post($draft);

        $this->assertSame('posted', $posted->status);
        $this->assertNotNull($posted->posted_at);
    }

    public function test_it_rejects_posting_non_draft_entries(): void
    {
        $this->seed();

        $cash = ChartOfAccount::query()->where('code', '1000')->firstOrFail();
        $sales = ChartOfAccount::query()->where('code', '4000')->firstOrFail();
        $service = app(JournalEntryService::class);

        $entry = $service->create([
            'entry_date' => now()->toDateString(),
        ], [
            ['chart_of_account_id' => $cash->id, 'debit' => 60, 'credit' => 0],
            ['chart_of_account_id' => $sales->id, 'debit' => 0, 'credit' => 60],
        ]);

        $service->post($entry);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('Only draft journal entries can be posted.');

        $service->post($entry->fresh());
    }
}

