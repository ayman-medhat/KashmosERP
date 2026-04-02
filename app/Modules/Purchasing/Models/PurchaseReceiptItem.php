<?php

namespace App\Modules\Purchasing\Models;

use App\Modules\MasterData\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReceiptItem extends Model
{
    protected $fillable = [
        'purchase_receipt_id',
        'purchase_order_item_id',
        'product_id',
        'ordered_qty',
        'received_qty',
        'unit_cost',
        'line_total',
    ];

    protected $casts = [
        'ordered_qty' => 'decimal:6',
        'received_qty' => 'decimal:6',
        'unit_cost' => 'decimal:4',
        'line_total' => 'decimal:4',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class, 'purchase_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
