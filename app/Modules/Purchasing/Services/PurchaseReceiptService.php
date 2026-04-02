<?php

namespace App\Modules\Purchasing\Services;

use App\Modules\Accounting\Services\WorkflowJournalPostingService;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseReceiptService
{
    private const ORDER_STATUS_APPROVED = 'approved';
    private const ORDER_STATUS_PARTIALLY_RECEIVED = 'partially_received';
    private const ORDER_STATUS_RECEIVED = 'received';

    public function __construct(
        protected StockMovementService $stockMovementService,
        protected WorkflowJournalPostingService $workflowJournalPostingService,
    ) {
    }

    public function receive(array $attributes, array $items): PurchaseReceipt
    {
        if ($items === []) {
            throw new \DomainException('At least one receipt item is required.');
        }

        return DB::transaction(function () use ($attributes, $items): PurchaseReceipt {
            $order = PurchaseOrder::query()
                ->whereKey($attributes['purchase_order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (! in_array($order->status, [self::ORDER_STATUS_APPROVED, self::ORDER_STATUS_PARTIALLY_RECEIVED], true)) {
                throw new \DomainException('Only approved purchase orders can be received.');
            }

            $order->load('items');
            $orderItems = $order->items->keyBy('id');

            $receipt = PurchaseReceipt::query()->create([
                'uuid' => (string) Str::uuid(),
                'receipt_no' => $attributes['receipt_no'] ?? $this->nextReceiptNo(),
                'purchase_order_id' => $order->id,
                'warehouse_id' => $order->warehouse_id,
                'received_date' => $attributes['received_date'],
                'status' => 'confirmed',
                'notes_translations' => $attributes['notes_translations'] ?? null,
                'confirmed_at' => now(),
                'created_by' => auth()->id(),
            ]);

            foreach ($items as $item) {
                $orderItemId = (int) $item['purchase_order_item_id'];
                $receivedQty = (float) $item['received_qty'];

                if ($receivedQty <= 0) {
                    throw new \DomainException('Received quantity must be greater than zero.');
                }

                $orderItem = $orderItems->get($orderItemId);

                if ($orderItem === null) {
                    throw new \DomainException('Receipt item does not belong to the selected purchase order.');
                }

                $remainingQty = (float) $orderItem->quantity - (float) $orderItem->received_qty;

                if ($receivedQty > $remainingQty) {
                    throw new \DomainException('Received quantity exceeds remaining purchase order quantity.');
                }

                $receipt->items()->create([
                    'purchase_order_item_id' => $orderItem->id,
                    'product_id' => $orderItem->product_id,
                    'ordered_qty' => $orderItem->quantity,
                    'received_qty' => $receivedQty,
                    'unit_cost' => $orderItem->unit_price,
                    'line_total' => $receivedQty * (float) $orderItem->unit_price,
                ]);

                $orderItem->forceFill([
                    'received_qty' => (float) $orderItem->received_qty + $receivedQty,
                ])->save();

                $this->stockMovementService->record([
                    'product_id' => $orderItem->product_id,
                    'warehouse_id' => $order->warehouse_id,
                    'movement_type' => 'purchase_receipt',
                    'reference_no' => $receipt->receipt_no,
                    'quantity' => $receivedQty,
                    'unit_cost' => (float) $orderItem->unit_price,
                    'notes_translations' => [
                        'en' => 'Stock in from purchase receipt',
                        'ar' => 'إضافة مخزون من استلام مشتريات',
                    ],
                ], $receipt);
            }

            $order->refresh()->load('items');
            $isFullyReceived = $order->items
                ->every(fn ($line): bool => (float) $line->received_qty >= (float) $line->quantity);

            $order->forceFill([
                'status' => $isFullyReceived ? self::ORDER_STATUS_RECEIVED : self::ORDER_STATUS_PARTIALLY_RECEIVED,
                'posted_to_stock_at' => $order->posted_to_stock_at ?? now(),
            ])->save();

            $this->workflowJournalPostingService->postPurchaseReceipt($receipt);

            return $receipt->refresh()->load(['items.product', 'order']);
        });
    }

    protected function nextReceiptNo(): string
    {
        $next = (int) PurchaseReceipt::query()->count() + 1;

        return 'GRN-'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }
}
