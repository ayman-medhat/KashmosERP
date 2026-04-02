<?php

namespace App\Modules\Sales\Models;

use App\Modules\MasterData\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesOrderItem extends Model
{
    protected $fillable = [
        'sales_order_id',
        'product_id',
        'quantity',
        'delivered_qty',
        'unit_price',
        'tax_rate',
        'line_subtotal',
        'line_tax',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'delivered_qty' => 'decimal:6',
        'unit_price' => 'decimal:4',
        'tax_rate' => 'decimal:4',
        'line_subtotal' => 'decimal:4',
        'line_tax' => 'decimal:4',
        'line_total' => 'decimal:4',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function deliveryItems(): HasMany
    {
        return $this->hasMany(SalesDeliveryItem::class, 'sales_order_item_id');
    }
}
