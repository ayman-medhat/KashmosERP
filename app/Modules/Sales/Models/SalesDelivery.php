<?php

namespace App\Modules\Sales\Models;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SalesDelivery extends Model
{
    protected $fillable = [
        'uuid',
        'delivery_no',
        'sales_order_id',
        'warehouse_id',
        'delivery_date',
        'status',
        'notes_translations',
        'confirmed_at',
        'created_by',
    ];

    protected $casts = [
        'delivery_date' => 'date',
        'notes_translations' => 'array',
        'confirmed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SalesDeliveryItem::class);
    }

    public function invoice(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SalesInvoice::class, 'sales_delivery_id')->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
