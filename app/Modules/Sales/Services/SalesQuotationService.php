<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Models\SalesQuotation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesQuotationService
{
    private const STATUS_DRAFT = 'draft';
    private const STATUS_SUBMITTED = 'submitted';
    private const STATUS_APPROVED = 'approved';
    private const STATUS_CONVERTED = 'converted';
    private const STATUS_CANCELLED = 'cancelled';

    public function __construct(
        protected SalesOrderService $salesOrderService,
    ) {
    }

    public function create(array $attributes, array $items): SalesQuotation
    {
        return DB::transaction(function () use ($attributes, $items): SalesQuotation {
            [$subtotal, $taxTotal, $grandTotal] = $this->calculateTotals($items);

            $quotation = SalesQuotation::query()->create([
                'uuid' => (string) Str::uuid(),
                'quotation_no' => $attributes['quotation_no'] ?? $this->nextQuotationNo(),
                'customer_id' => $attributes['customer_id'],
                'warehouse_id' => $attributes['warehouse_id'],
                'quotation_date' => $attributes['quotation_date'],
                'status' => self::STATUS_DRAFT,
                'subtotal' => $subtotal,
                'tax_total' => $taxTotal,
                'grand_total' => $grandTotal,
                'notes_translations' => $attributes['notes_translations'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $lineSubtotal = ((float) $item['quantity']) * ((float) $item['unit_price']);
                $lineTax = $lineSubtotal * (((float) ($item['tax_rate'] ?? 0)) / 100);

                $quotation->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineSubtotal + $lineTax,
                ]);
            }

            return $quotation->refresh()->load('items');
        });
    }

    public function submit(SalesQuotation $quotation): SalesQuotation
    {
        if ($quotation->status !== self::STATUS_DRAFT) {
            throw new \DomainException('Only draft quotations can be submitted.');
        }

        $quotation->forceFill([
            'status' => self::STATUS_SUBMITTED,
        ])->save();

        return $quotation->refresh();
    }

    public function approve(SalesQuotation $quotation): SalesQuotation
    {
        if ($quotation->status !== self::STATUS_SUBMITTED) {
            throw new \DomainException('Only submitted quotations can be approved.');
        }

        $quotation->forceFill([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
        ])->save();

        return $quotation->refresh();
    }

    public function convertToSalesOrder(SalesQuotation $quotation): SalesOrder
    {
        return DB::transaction(function () use ($quotation): SalesOrder {
            $quotation->loadMissing('items');

            if ($quotation->status === self::STATUS_CONVERTED || $quotation->converted_sales_order_id) {
                return $quotation->convertedSalesOrder()->firstOrFail();
            }

            if ($quotation->status !== self::STATUS_APPROVED) {
                throw new \DomainException('Only approved quotations can be converted.');
            }

            $order = $this->salesOrderService->create([
                'customer_id' => $quotation->customer_id,
                'warehouse_id' => $quotation->warehouse_id,
                'order_date' => now()->toDateString(),
                'notes_translations' => $quotation->notes_translations,
            ], $quotation->items->map(fn ($item): array => [
                'product_id' => $item->product_id,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'tax_rate' => (float) $item->tax_rate,
            ])->all());

            $quotation->forceFill([
                'status' => self::STATUS_CONVERTED,
                'converted_sales_order_id' => $order->id,
                'converted_at' => now(),
            ])->save();

            return $order->refresh();
        });
    }

    public function cancel(SalesQuotation $quotation): SalesQuotation
    {
        if ($quotation->status === self::STATUS_CONVERTED) {
            throw new \DomainException('Converted quotations cannot be cancelled.');
        }

        if (! in_array($quotation->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED], true)) {
            throw new \DomainException('Only draft or submitted quotations can be cancelled.');
        }

        $quotation->forceFill([
            'status' => self::STATUS_CANCELLED,
        ])->save();

        return $quotation->refresh();
    }

    protected function calculateTotals(array $items): array
    {
        $subtotal = 0.0;
        $taxTotal = 0.0;

        foreach ($items as $item) {
            $lineSubtotal = ((float) $item['quantity']) * ((float) $item['unit_price']);
            $lineTax = $lineSubtotal * (((float) ($item['tax_rate'] ?? 0)) / 100);
            $subtotal += $lineSubtotal;
            $taxTotal += $lineTax;
        }

        return [$subtotal, $taxTotal, $subtotal + $taxTotal];
    }

    protected function nextQuotationNo(): string
    {
        $next = (int) SalesQuotation::query()->count() + 1;

        return 'SQ-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
