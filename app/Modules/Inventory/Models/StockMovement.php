<?php

namespace App\Modules\Inventory\Models;

use App\Core\Models\User;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class StockMovement extends Model
{
    protected $fillable = [
        'uuid',
        'product_id',
        'warehouse_id',
        'movement_type',
        'source_type',
        'source_id',
        'reference_no',
        'quantity',
        'balance_after',
        'unit_cost',
        'notes_translations',
        'created_by',
    ];

    protected $casts = [
        'quantity' => 'decimal:6',
        'balance_after' => 'decimal:6',
        'unit_cost' => 'decimal:4',
        'notes_translations' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
