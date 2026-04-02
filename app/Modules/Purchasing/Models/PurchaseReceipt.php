<?php

namespace App\Modules\Purchasing\Models;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReceipt extends Model
{
    protected $fillable = [
        'uuid',
        'receipt_no',
        'purchase_order_id',
        'warehouse_id',
        'received_date',
        'status',
        'notes_translations',
        'confirmed_at',
        'created_by',
    ];

    protected $casts = [
        'received_date' => 'date',
        'notes_translations' => 'array',
        'confirmed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class, 'purchase_order_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReceiptItem::class);
    }

    public function supplierBill(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SupplierBill::class, 'purchase_receipt_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
