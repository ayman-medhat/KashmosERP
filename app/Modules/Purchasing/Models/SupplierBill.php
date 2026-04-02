<?php

namespace App\Modules\Purchasing\Models;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SupplierBill extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'bill_no',
        'purchase_receipt_id',
        'purchase_order_id',
        'supplier_id',
        'bill_date',
        'due_date',
        'status',
        'subtotal',
        'tax_total',
        'grand_total',
        'paid_total',
        'notes_translations',
        'posted_at',
        'created_by',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'grand_total' => 'decimal:4',
        'paid_total' => 'decimal:4',
        'notes_translations' => 'array',
        'posted_at' => 'datetime',
    ];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierBillItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getOutstandingAmountAttribute(): float
    {
        return max(0.0, (float) $this->grand_total - (float) $this->paid_total);
    }
}

