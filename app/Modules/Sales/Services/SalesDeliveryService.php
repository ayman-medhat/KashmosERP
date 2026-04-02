<?php

namespace App\Modules\Sales\Services;

use App\Modules\Accounting\Services\WorkflowJournalPostingService;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Models\SalesOrder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SalesDeliveryService
{
    private const ORDER_STATUS_APPROVED = 'approved';
    private const ORDER_STATUS_PARTIALLY_DELIVERED = 'partially_delivered';
    private const ORDER_STATUS_DELIVERED = 'delivered';

    public function __construct(
        protected StockMovementService $stockMovementService,
        protected WorkflowJournalPostingService $workflowJournalPostingService,
    ) {
    }

    public function deliver(array $attributes, array $items): SalesDelivery
    {
        if ($items === []) {
            throw new \DomainException('At least one delivery item is required.');
        }

        return DB::transaction(function () use ($attributes, $items): SalesDelivery {
            $order = SalesOrder::query()
                ->whereKey($attributes['sales_order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($order->status, [self::ORDER_STATUS_APPROVED, self::ORDER_STATUS_PARTIALLY_DELIVERED], true)) {
                throw new \DomainException('Only approved sales orders can be delivered.');
            }

            $order->load('items');
            $orderItems = $order->items->keyBy('id');

            $delivery = SalesDelivery::query()->create([
                'uuid' => (string) Str::uuid(),
                'delivery_no' => $attributes['delivery_no'] ?? $this->nextDeliveryNo(),
                'sales_order_id' => $order->id,
                'warehouse_id' => $order->warehouse_id,
                'delivery_date' => $attributes['delivery_date'],
                'status' => 'confirmed',
                'notes_translations' => $attributes['notes_translations'] ?? null,
                'confirmed_at' => now(),
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $orderItemId = (int) $item['sales_order_item_id'];
                $deliveredQty = (float) $item['delivered_qty'];

                if ($deliveredQty <= 0) {
                    throw new \DomainException('Delivered quantity must be greater than zero.');
                }

                $orderItem = $orderItems->get($orderItemId);

                if ($orderItem === null) {
                    throw new \DomainException('Delivery item does not belong to the selected sales order.');
                }

                $remainingQty = (float) $orderItem->quantity - (float) $orderItem->delivered_qty;

                if ($deliveredQty > $remainingQty) {
                    throw new \DomainException('Delivered quantity exceeds remaining sales order quantity.');
                }

                $delivery->items()->create([
                    'sales_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'ordered_qty' => $orderItem->quantity,
                    'delivered_qty' => $deliveredQty,
                    'unit_price' => $orderItem->unit_price,
                    'line_total' => $deliveredQty * (float) $orderItem->unit_price,
                ]);

                $orderItem->forceFill([
                    'delivered_qty' => (float) $orderItem->delivered_qty + $deliveredQty,
                ])->save();

                $this->stockMovementService->record([
                    'product_id' => $orderItem->product_id,
                    'warehouse_id' => $order->warehouse_id,
                    'movement_type' => 'sales_delivery',
                    'reference_no' => $delivery->delivery_no,
                    'quantity' => -1 * $deliveredQty,
                    'unit_cost' => (float) $orderItem->unit_price,
                    'notes_translations' => [
                        'en' => 'Stock out from sales delivery',
                        'ar' => 'صرف مخزون من تسليم مبيعات',
                    ],
                ], $delivery);
            }

            $order->refresh()->load('items');
            $isFullyDelivered = $order->items
                ->every(fn ($line): bool => (float) $line->delivered_qty >= (float) $line->quantity);

            $order->forceFill([
                'status' => $isFullyDelivered ? self::ORDER_STATUS_DELIVERED : self::ORDER_STATUS_PARTIALLY_DELIVERED,
                'posted_to_stock_at' => $order->posted_to_stock_at ?? now(),
            ])->save();

            $this->workflowJournalPostingService->postSalesDelivery($delivery);

            return $delivery->refresh()->load(['items.product', 'order']);
        });
    }

    protected function nextDeliveryNo(): string
    {
        $next = (int) SalesDelivery::query()->count() + 1;

        return 'SDN-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
