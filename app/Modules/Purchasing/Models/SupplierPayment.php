<?php

namespace App\Modules\Purchasing\Models;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Supplier;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    protected $fillable = [
        'uuid',
        'payment_no',
        'supplier_bill_id',
        'supplier_id',
        'payment_date',
        'status',
        'amount',
        'payment_method',
        'reference_no',
        'notes_translations',
        'confirmed_at',
        'posted_at',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:4',
        'notes_translations' => 'array',
        'confirmed_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(SupplierBill::class, 'supplier_bill_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

