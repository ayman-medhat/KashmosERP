<?php

namespace App\Modules\Sales\Models;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class SalesQuotation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'quotation_no',
        'customer_id',
        'warehouse_id',
        'quotation_date',
        'status',
        'subtotal',
        'tax_total',
        'grand_total',
        'notes_translations',
        'converted_sales_order_id',
        'approved_at',
        'converted_at',
        'created_by',
    ];

    protected $casts = [
        'quotation_date' => 'date',
        'subtotal' => 'decimal:4',
        'tax_total' => 'decimal:4',
        'grand_total' => 'decimal:4',
        'notes_translations' => 'array',
        'approved_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesQuotationItem::class);
    }

    public function convertedSalesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'converted_sales_order_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
