<?php

namespace App\Modules\Sales\Models;

use App\Modules\MasterData\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesQuotationItem extends Model
{
    protected $fillable = [
        'sales_quotation_id',
        'product_id',
        'quantity',
        'unit_price',
        'tax_rate',
        'line_subtotal',
        'line_tax',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_price' => 'decimal:4',
        'tax_rate' => 'decimal:4',
        'line_subtotal' => 'decimal:4',
        'line_tax' => 'decimal:4',
        'line_total' => 'decimal:4',
    ];

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(SalesQuotation::class, 'sales_quotation_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
