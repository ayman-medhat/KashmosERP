<?php

namespace App\Modules\Purchasing\Services;

use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Purchasing\Models\SupplierBill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SupplierBillService
{
    private const STATUS_DRAFT = 'draft';
    private const STATUS_POSTED = 'posted';
    private const STATUS_PARTIALLY_PAID = 'partially_paid';
    private const STATUS_PAID = 'paid';
    private const STATUS_CANCELLED = 'cancelled';

    public function createFromReceipt(array $attributes): SupplierBill
    {
        return DB::transaction(function () use ($attributes): SupplierBill {
            $receipt = PurchaseReceipt::query()
                ->whereKey($attributes['purchase_receipt_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $receipt->loadMissing(['order', 'items.purchaseOrderItem']);

            if ($receipt->status !== 'confirmed') {
                throw new \DomainException('Only confirmed purchase receipts can be billed.');
            }

            if (SupplierBill::query()->where('purchase_receipt_id', $receipt->id)->exists()) {
                throw new \DomainException('A supplier bill already exists for this purchase receipt.');
            }

            if ($receipt->items->isEmpty()) {
                throw new \DomainException('Purchase receipt has no items to bill.');
            }

            $billDate = $attributes['bill_date'] ?? now()->toDateString();
            $dueDate = $attributes['due_date'] ?? $billDate;

            $bill = SupplierBill::query()->create([
                'uuid' => (string) Str::uuid(),
                'bill_no' => $attributes['bill_no'] ?? $this->nextBillNo(),
                'purchase_receipt_id' => $receipt->id,
                'purchase_order_id' => $receipt->purchase_order_id,
                'supplier_id' => $receipt->order->supplier_id,
                'bill_date' => $billDate,
                'due_date' => $dueDate,
                'status' => self::STATUS_DRAFT,
                'subtotal' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'paid_total' => 0,
                'notes_translations' => $attributes['notes_translations'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $subtotal = 0.0;
            $taxTotal = 0.0;

            foreach ($receipt->items as $receiptItem) {
                $quantity = (float) $receiptItem->received_qty;
                $unitCost = (float) $receiptItem->unit_cost;
                $taxRate = (float) ($receiptItem->purchaseOrderItem?->tax_rate ?? 0);
                $lineSubtotal = round($quantity * $unitCost, 4);
                $lineTax = round($lineSubtotal * ($taxRate / 100), 4);
                $lineTotal = $lineSubtotal + $lineTax;

                $bill->items()->create([
                    'purchase_receipt_item_id' => $receiptItem->id,
                    'product_id' => $receiptItem->product_id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'tax_rate' => $taxRate,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }

            $bill->forceFill([
                'subtotal' => round($subtotal, 4),
                'tax_total' => round($taxTotal, 4),
                'grand_total' => round($subtotal + $taxTotal, 4),
            ])->save();

            return $bill->refresh()->load(['items.product', 'receipt', 'supplier']);
        });
    }

    public function post(SupplierBill $bill): SupplierBill
    {
        return DB::transaction(function () use ($bill): SupplierBill {
            $bill = SupplierBill::query()
                ->whereKey($bill->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($bill->status === self::STATUS_POSTED) {
                return $bill->refresh()->load(['items.product', 'receipt', 'supplier']);
            }

            if ($bill->status !== self::STATUS_DRAFT) {
                throw new \DomainException('Only draft supplier bills can be posted.');
            }

            if ((float) $bill->grand_total <= 0) {
                throw new \DomainException('Supplier bill total must be greater than zero.');
            }

            $bill->forceFill([
                'status' => self::STATUS_POSTED,
                'posted_at' => now(),
            ])->save();

            return $bill->refresh()->load(['items.product', 'receipt', 'supplier']);
        });
    }

    public function applyPayment(SupplierBill $bill, float $amount): SupplierBill
    {
        return DB::transaction(function () use ($bill, $amount): SupplierBill {
            $bill = SupplierBill::query()
                ->whereKey($bill->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($bill->status, [self::STATUS_POSTED, self::STATUS_PARTIALLY_PAID, self::STATUS_PAID], true)) {
                throw new \DomainException('Supplier bill must be posted before applying payments.');
            }

            if ($amount <= 0) {
                throw new \DomainException('Payment amount must be greater than zero.');
            }

            $outstanding = round((float) $bill->grand_total - (float) $bill->paid_total, 4);
            if ($amount > $outstanding) {
                throw new \DomainException('Payment amount cannot exceed supplier bill outstanding balance.');
            }

            $newPaidTotal = round((float) $bill->paid_total + $amount, 4);
            $newStatus = $newPaidTotal >= (float) $bill->grand_total
                ? self::STATUS_PAID
                : self::STATUS_PARTIALLY_PAID;

            $bill->forceFill([
                'paid_total' => $newPaidTotal,
                'status' => $newStatus,
            ])->save();

            return $bill->refresh()->load(['items.product', 'payments']);
        });
    }

    protected function nextBillNo(): string
    {
        $next = (int) SupplierBill::query()->count() + 1;

        return 'SB-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

