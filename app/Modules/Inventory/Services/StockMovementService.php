<?php

namespace App\Modules\Inventory\Services;

use App\Core\Services\SettingsService;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StockMovementService
{
    public function __construct(
        protected SettingsService $settingsService,
    ) {
    }

    public function currentStock(int $productId, int $warehouseId): float
    {
        return (float) StockMovement::query()
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->latest('id')
            ->value('balance_after') ?? 0.0;
    }

    public function record(array $attributes, ?Model $source = null): StockMovement
    {
        return DB::transaction(function () use ($attributes, $source): StockMovement {
            $productId = (int) $attributes['product_id'];
            $warehouseId = (int) $attributes['warehouse_id'];
            $quantity = (float) $attributes['quantity'];
            $unitCost = array_key_exists('unit_cost', $attributes) && $attributes['unit_cost'] !== null && $attributes['unit_cost'] !== ''
                ? (float) $attributes['unit_cost']
                : null;

            $this->ensureQuantityAllowed($quantity);
            $this->ensureUnitCostAllowed($unitCost);

            $currentStock = $this->currentStock($productId, $warehouseId);
            $newBalance = $currentStock + $quantity;

            $this->ensureStockChangeAllowed($newBalance);

            return StockMovement::query()->create([
                'uuid' => (string) Str::uuid(),
                'product_id' => $productId,
                'warehouse_id' => $warehouseId,
                'movement_type' => $attributes['movement_type'],
                'source_type' => $source?->getMorphClass() ?? $attributes['source_type'] ?? null,
                'source_id' => $source?->getKey() ?? $attributes['source_id'] ?? null,
                'reference_no' => $attributes['reference_no'] ?? null,
                'quantity' => $quantity,
                'balance_after' => $newBalance,
                'unit_cost' => $unitCost,
                'notes_translations' => $attributes['notes_translations'] ?? null,
                'created_by' => auth()->id(),
            ]);
        });
    }

    public function openingBalance(Product $product, Warehouse $warehouse, float $quantity, ?float $unitCost = null): StockMovement
    {
        return $this->record([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'movement_type' => 'opening',
            'reference_no' => 'OPENING-'.$product->sku,
            'quantity' => $quantity,
            'unit_cost' => $unitCost,
            'notes_translations' => [
                'en' => 'Opening balance',
                'ar' => 'رصيد افتتاحي',
            ],
        ]);
    }

    protected function ensureStockChangeAllowed(float $newBalance): void
    {
        $allowNegativeStock = (bool) $this->settingsService->get('inventory', 'allow_negative_stock', false);

        if (! $allowNegativeStock && $newBalance < 0) {
            throw new \DomainException('Negative stock is not allowed by system settings.');
        }
    }

    protected function ensureQuantityAllowed(float $quantity): void
    {
        if ($quantity == 0.0) {
            throw new \DomainException('Stock movement quantity cannot be zero.');
        }
    }

    protected function ensureUnitCostAllowed(?float $unitCost): void
    {
        if ($unitCost !== null && $unitCost < 0) {
            throw new \DomainException('Unit cost cannot be negative.');
        }
    }
}
