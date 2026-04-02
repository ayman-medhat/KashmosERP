<?php

namespace App\Modules\Purchasing\Services;

use App\Modules\Purchasing\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseOrderService
{
    private const STATUS_DRAFT = 'draft';
    private const STATUS_SUBMITTED = 'submitted';
    private const STATUS_APPROVED = 'approved';
    private const STATUS_PARTIALLY_RECEIVED = 'partially_received';
    private const STATUS_RECEIVED = 'received';
    private const STATUS_CANCELLED = 'cancelled';

    public function create(array $attributes, array $items): PurchaseOrder
    {
        return DB::transaction(function () use ($attributes, $items): PurchaseOrder {
            [$subtotal, $taxTotal, $grandTotal] = $this->calculateTotals($items);

            $order = PurchaseOrder::query()->create([
                'uuid' => (string) Str::uuid(),
                'order_no' => $attributes['order_no'] ?? $this->nextOrderNo(),
                'supplier_id' => $attributes['supplier_id'],
                'warehouse_id' => $attributes['warehouse_id'],
                'order_date' => $attributes['order_date'],
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

                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'tax_rate' => $item['tax_rate'] ?? 0,
                    'line_subtotal' => $lineSubtotal,
                    'line_tax' => $lineTax,
                    'line_total' => $lineSubtotal + $lineTax,
                ]);
            }

            return $order->refresh()->load('items');
        });
    }

    public function approve(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status === self::STATUS_APPROVED) {
            return $order;
        }

        if ($order->status !== self::STATUS_SUBMITTED) {
            throw new \DomainException('Only submitted purchase orders can be approved.');
        }

        $order->forceFill([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
        ])->save();

        return $order->refresh();
    }

    public function submit(PurchaseOrder $order): PurchaseOrder
    {
        if ($order->status !== self::STATUS_DRAFT) {
            throw new \DomainException('Only draft purchase orders can be submitted.');
        }

        $order->forceFill([
            'status' => self::STATUS_SUBMITTED,
        ])->save();

        return $order->refresh();
    }

    public function cancel(PurchaseOrder $order): PurchaseOrder
    {
        if (in_array($order->status, [self::STATUS_APPROVED, self::STATUS_PARTIALLY_RECEIVED, self::STATUS_RECEIVED], true)) {
            throw new \DomainException('Approved or received purchase orders cannot be cancelled.');
        }

        if (! in_array($order->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED], true)) {
            throw new \DomainException('Only draft or submitted purchase orders can be cancelled.');
        }

        $order->forceFill([
            'status' => self::STATUS_CANCELLED,
        ])->save();

        return $order->refresh();
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

    protected function nextOrderNo(): string
    {
        $next = (int) PurchaseOrder::query()->count() + 1;

        return 'PO-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
