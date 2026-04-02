<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Sales\Models\SalesReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesReceiptService
{
    public function __construct(
        protected SalesInvoiceService $salesInvoiceService,
        protected \App\Modules\Accounting\Services\WorkflowJournalPostingService $workflowJournalPostingService,
    ) {
    }

    public function receive(array $attributes): SalesReceipt
    {
        return DB::transaction(function () use ($attributes): SalesReceipt {
            $invoice = SalesInvoice::query()
                ->whereKey($attributes['sales_invoice_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $invoice->loadMissing('customer');

            $amount = round((float) $attributes['amount'], 4);
            if ($amount <= 0) {
                throw new \DomainException('Receipt amount must be greater than zero.');
            }

            $receipt = SalesReceipt::query()->create([
                'uuid' => (string) Str::uuid(),
                'receipt_no' => $attributes['receipt_no'] ?? $this->nextReceiptNo(),
                'sales_invoice_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'receipt_date' => $attributes['receipt_date'] ?? now()->toDateString(),
                'status' => 'confirmed',
                'amount' => $amount,
                'payment_method' => $attributes['payment_method'] ?? null,
                'reference_no' => $attributes['reference_no'] ?? null,
                'notes_translations' => $attributes['notes_translations'] ?? null,
                'confirmed_at' => now(),
                'posted_at' => now(),
                'created_by' => auth()->id(),
            ]);

            $this->workflowJournalPostingService->postSalesReceipt($receipt);
            $this->salesInvoiceService->applyReceipt($invoice, $amount);

            return $receipt->refresh()->load(['invoice', 'customer']);
        });
    }

    protected function nextReceiptNo(): string
    {
        $next = (int) SalesReceipt::query()->count() + 1;

        return 'SR-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

