<?php

namespace App\Modules\Purchasing\Models;

use App\Modules\MasterData\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBillItem extends Model
{
    protected $fillable = [
        'supplier_bill_id',
        'purchase_receipt_item_id',
        'product_id',
        'quantity',
        'unit_cost',
        'tax_rate',
        'line_subtotal',
        'line_tax',
        'line_total',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'unit_cost' => 'decimal:4',
        'tax_rate' => 'decimal:4',
        'line_subtotal' => 'decimal:4',
        'line_tax' => 'decimal:4',
        'line_total' => 'decimal:4',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class, 'supplier_bill_id');
    }

    public function receiptItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceiptItem::class, 'purchase_receipt_item_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

