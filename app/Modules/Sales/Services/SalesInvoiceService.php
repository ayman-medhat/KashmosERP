<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Models\SalesInvoice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesInvoiceService
{
    private const STATUS_DRAFT = 'draft';
    private const STATUS_POSTED = 'posted';
    private const STATUS_PARTIALLY_PAID = 'partially_paid';
    private const STATUS_PAID = 'paid';
    private const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        protected \App\Modules\Accounting\Services\WorkflowJournalPostingService $workflowJournalPostingService,
    ) {
    }

    public function createFromDelivery(array $attributes): SalesInvoice
    {
        return DB::transaction(function () use ($attributes): SalesInvoice {
            $delivery = SalesDelivery::query()
                ->whereKey($attributes['sales_delivery_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $delivery->loadMissing(['order', 'items.salesOrderItem']);

            if ($delivery->status !== 'confirmed') {
                throw new \DomainException('Only confirmed sales deliveries can be invoiced.');
            }

            if (SalesInvoice::query()->where('sales_delivery_id', $delivery->id)->exists()) {
                throw new \DomainException('An invoice already exists for this sales delivery.');
            }

            if ($delivery->items->isEmpty()) {
                throw new \DomainException('Sales delivery has no items to invoice.');
            }

            $invoiceDate = $attributes['invoice_date'] ?? now()->toDateString();
            $dueDate = $attributes['due_date'] ?? $invoiceDate;

            $invoice = SalesInvoice::query()->create([
                'uuid' => (string) Str::uuid(),
                'invoice_no' => $attributes['invoice_no'] ?? $this->nextInvoiceNo(),
                'sales_delivery_id' => $delivery->id,
                'sales_order_id' => $delivery->sales_order_id,
                'customer_id' => $delivery->order->customer_id,
                'invoice_date' => $invoiceDate,
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

            foreach ($delivery->items as $deliveryItem) {
                $quantity = (float) $deliveryItem->delivered_qty;
                $unitPrice = (float) $deliveryItem->unit_price;
                $taxRate = (float) ($deliveryItem->salesOrderItem?->tax_rate ?? 0);
                $lineSubtotal = round($quantity * $unitPrice, 4);
                $lineTax = round($lineSubtotal * ($taxRate / 100), 4);
                $lineTotal = $lineSubtotal + $lineTax;

                $invoice->items()->create([
                    'sales_delivery_item_id' => $deliveryItem->id,
                    'product_id' => $deliveryItem->product_id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'tax_rate' => $taxRate,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineTotal,
                ]);

                $subtotal += $lineSubtotal;
                $taxTotal += $lineTax;
            }

            $invoice->forceFill([
                'subtotal' => round($subtotal, 4),
                'tax_total' => round($taxTotal, 4),
                'grand_total' => round($subtotal + $taxTotal, 4),
            ])->save();

            return $invoice->refresh()->load(['items.product', 'delivery', 'customer']);
        });
    }

    public function post(SalesInvoice $invoice): SalesInvoice
    {
        return DB::transaction(function () use ($invoice): SalesInvoice {
            $invoice = SalesInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($invoice->status === self::STATUS_POSTED) {
                return $invoice->refresh()->load(['items.product', 'delivery', 'customer']);
            }

            if ($invoice->status !== self::STATUS_DRAFT) {
                throw new \DomainException('Only draft sales invoices can be posted.');
            }

            if ((float) $invoice->grand_total <= 0) {
                throw new \DomainException('Sales invoice total must be greater than zero.');
            }

            $this->workflowJournalPostingService->postSalesInvoice($invoice);

            $invoice->forceFill([
                'status' => self::STATUS_POSTED,
                'posted_at' => now(),
            ])->save();

            return $invoice->refresh()->load(['items.product', 'delivery', 'customer']);
        });
    }

    public function applyReceipt(SalesInvoice $invoice, float $amount): SalesInvoice
    {
        return DB::transaction(function () use ($invoice, $amount): SalesInvoice {
            $invoice = SalesInvoice::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($invoice->status, [self::STATUS_POSTED, self::STATUS_PARTIALLY_PAID, self::STATUS_PAID], true)) {
                throw new \DomainException('Sales invoice must be posted before receiving payments.');
            }

            if ($amount <= 0) {
                throw new \DomainException('Receipt amount must be greater than zero.');
            }

            $outstanding = round((float) $invoice->grand_total - (float) $invoice->paid_total, 4);

            if ($amount > $outstanding) {
                throw new \DomainException('Receipt amount cannot exceed invoice outstanding balance.');
            }

            $newPaidTotal = round((float) $invoice->paid_total + $amount, 4);
            $newStatus = $newPaidTotal >= (float) $invoice->grand_total
                ? self::STATUS_PAID
                : self::STATUS_PARTIALLY_PAID;

            $invoice->forceFill([
                'paid_total' => $newPaidTotal,
                'status' => $newStatus,
            ])->save();

            return $invoice->refresh()->load(['items.product', 'receipts']);
        });
    }

    protected function nextInvoiceNo(): string
    {
        $next = (int) SalesInvoice::query()->count() + 1;

        return 'SI-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}

