<?php

namespace App\Modules\Sales\Models;

use App\Modules\MasterData\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesDeliveryItem extends Model
{
    protected $fillable = [
        'sales_delivery_id',
        'sales_order_item_id',
        'product_id',
        'ordered_qty',
        'delivered_qty',
        'unit_price',
        'line_total',
    ];

    protected $casts = [
        'ordered_qty' => 'decimal:6',
        'delivered_qty' => 'decimal:6',
        'unit_price' => 'decimal:4',
        'line_total' => 'decimal:4',
    ];

    public function delivery(): BelongsTo
    {
        return $this->belongsTo(SalesDelivery::class, 'sales_delivery_id');
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class, 'sales_order_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
