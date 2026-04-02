<?php

namespace App\Modules\Purchasing\Services;

use App\Modules\Purchasing\Models\SupplierBill;
use App\Modules\Purchasing\Models\SupplierPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierPaymentService
{
    public function __construct(
        protected SupplierBillService $supplierBillService,
        protected \App\Modules\Accounting\Services\WorkflowJournalPostingService $workflowJournalPostingService,
    ) {
    }

    public function pay(array $attributes): SupplierPayment
    {
        return DB::transaction(function () use ($attributes): SupplierPayment {
            $bill = SupplierBill::query()
                ->whereKey($attributes['supplier_bill_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $bill->loadMissing('supplier');

            $amount = round((float) $attributes['amount'], 4);
            if ($amount <= 0) {
                throw new \DomainException('Payment amount must be greater than zero.');
            }

            $payment = SupplierPayment::query()->create([
                'uuid' => (string) Str::uuid(),
                'payment_no' => $attributes['payment_no'] ?? $this->nextPaymentNo(),
                'supplier_bill_id' => $bill->id,
                'supplier_id' => $bill->supplier_id,
                'payment_date' => $attributes['payment_date'] ?? now()->toDateString(),
                'status' => 'confirmed',
                'amount' => $amount,
                'payment_method' => $attributes['payment_method'] ?? null,
                'reference_no' => $attributes['reference_no'] ?? null,
                'notes_translations' => $attributes['notes_translations'] ?? null,
                'confirmed_at' => now(),
                'posted_at' => now(),
                'created_by' => auth()->id(),
            ]);

            $this->workflowJournalPostingService->postSupplierPayment($payment);
            $this->supplierBillService->applyPayment($bill, $amount);

            return $payment->refresh()->load(['bill', 'supplier']);
        });
    }

    protected function nextPaymentNo(): string
    {
        $next = (int) SupplierPayment::query()->count() + 1;

        return 'SP-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

