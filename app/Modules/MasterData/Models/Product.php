<?php

namespace App\Modules\MasterData\Models;

use App\Modules\MasterData\Models\Concerns\HasTranslatedName;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasTranslatedName;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'sku',
        'name_translations',
        'description_translations',
        'product_category_id',
        'unit_id',
        'tax_id',
        'cost_price',
        'sale_price',
        'opening_stock',
        'reorder_level',
        'track_stock',
        'is_active',
    ];

    protected $casts = [
        'name_translations' => 'array',
        'description_translations' => 'array',
        'cost_price' => 'decimal:4',
        'sale_price' => 'decimal:4',
        'opening_stock' => 'decimal:6',
        'reorder_level' => 'decimal:6',
        'track_stock' => 'bool',
        'is_active' => 'bool',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'product_category_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function tax(): BelongsTo
    {
        return $this->belongsTo(Tax::class);
    }
}
