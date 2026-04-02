<?php

namespace App\Modules\MasterData\Services;

use App\Core\Services\SettingsService;
use App\Modules\MasterData\Models\Product;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        protected SettingsService $settingsService,
    ) {
    }

    public function create(array $attributes): Product
    {
        $attributes['uuid'] ??= (string) Str::uuid();

        return Product::query()->create($attributes);
    }

    public function update(Product $product, array $attributes): Product
    {
        $product->fill($attributes)->save();

        return $product->refresh();
    }

    public function ensureStockChangeAllowed(float $change, float $currentStock): void
    {
        $allowNegativeStock = (bool) $this->settingsService->get('inventory', 'allow_negative_stock', false);

        if (! $allowNegativeStock && ($currentStock + $change) < 0) {
            throw new \DomainException('Negative stock is not allowed by system settings.');
        }
    }
}
