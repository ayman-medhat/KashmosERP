<?php

namespace App\Modules\Sales\Models;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Customer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalesReceipt extends Model
{
    protected $fillable = [
        'uuid',
        'receipt_no',
        'sales_invoice_id',
        'customer_id',
        'receipt_date',
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
        'receipt_date' => 'date',
        'amount' => 'decimal:4',
        'notes_translations' => 'array',
        'confirmed_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

