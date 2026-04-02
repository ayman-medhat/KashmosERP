<?php

namespace App\Modules\Sales\Services;

use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesOrderService
{
    private const STATUS_DRAFT = 'draft';
    private const STATUS_SUBMITTED = 'submitted';
    private const STATUS_APPROVED = 'approved';
    private const STATUS_PARTIALLY_DELIVERED = 'partially_delivered';
    private const STATUS_DELIVERED = 'delivered';
    private const STATUS_CANCELLED = 'cancelled';

    public function create(array $attributes, array $items): SalesOrder
    {
        return DB::transaction(function () use ($attributes, $items): SalesOrder {
            [$subtotal, $taxTotal, $grandTotal] = $this->calculateTotals($items);

            $order = SalesOrder::query()->create([
                'uuid' => (string) Str::uuid(),
                'order_no' => $attributes['order_no'] ?? $this->nextOrderNo(),
                'customer_id' => $attributes['customer_id'],
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

    public function approve(SalesOrder $order): SalesOrder
    {
        if ($order->status === self::STATUS_APPROVED) {
            return $order;
        }

        if ($order->status !== self::STATUS_SUBMITTED) {
            throw new \DomainException('Only submitted sales orders can be approved.');
        }

        $order->forceFill([
            'status' => self::STATUS_APPROVED,
            'approved_at' => now(),
        ])->save();

        return $order->refresh();
    }

    public function submit(SalesOrder $order): SalesOrder
    {
        if ($order->status !== self::STATUS_DRAFT) {
            throw new \DomainException('Only draft sales orders can be submitted.');
        }

        $order->forceFill([
            'status' => self::STATUS_SUBMITTED,
        ])->save();

        return $order->refresh();
    }

    public function cancel(SalesOrder $order): SalesOrder
    {
        if (in_array($order->status, [self::STATUS_APPROVED, self::STATUS_PARTIALLY_DELIVERED, self::STATUS_DELIVERED], true)) {
            throw new \DomainException('Approved or delivered sales orders cannot be cancelled.');
        }

        if (! in_array($order->status, [self::STATUS_DRAFT, self::STATUS_SUBMITTED], true)) {
            throw new \DomainException('Only draft or submitted sales orders can be cancelled.');
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
        $next = (int) SalesOrder::query()->count() + 1;

        return 'SO-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
