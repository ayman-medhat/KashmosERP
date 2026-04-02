<?php

namespace App\Core\Services;

use App\Modules\Inventory\Models\StockMovement;
use App\Modules\MasterData\Models\Product;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Models\SalesOrder;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class DashboardMetricsService
{
    /**
     * @return array<string, int>
     */
    public function summary(): array
    {
        return [
            'today_sales_deliveries' => SalesDelivery::query()
                ->whereDate('delivery_date', today())
                ->count(),
            'today_purchase_receipts' => PurchaseReceipt::query()
                ->whereDate('received_date', today())
                ->count(),
            'open_approved_sales_orders' => SalesOrder::query()
                ->where('status', 'approved')
                ->count(),
            'open_approved_purchase_orders' => PurchaseOrder::query()
                ->where('status', 'approved')
                ->count(),
            'partially_delivered_sales_orders' => SalesOrder::query()
                ->where('status', 'partially_delivered')
                ->count(),
            'partially_received_purchase_orders' => PurchaseOrder::query()
                ->where('status', 'partially_received')
                ->count(),
            'low_stock_alert_products' => count($this->lowStockProductIds()),
        ];
    }

    /**
     * @return array{
     *     open_approved_sales_orders: int,
     *     partially_delivered_sales_orders: int,
     *     open_approved_purchase_orders: int,
     *     partially_received_purchase_orders: int
     * }
     */
    public function backlogQuality(): array
    {
        return [
            'open_approved_sales_orders' => SalesOrder::query()
                ->where('status', 'approved')
                ->count(),
            'partially_delivered_sales_orders' => SalesOrder::query()
                ->where('status', 'partially_delivered')
                ->count(),
            'open_approved_purchase_orders' => PurchaseOrder::query()
                ->where('status', 'approved')
                ->count(),
            'partially_received_purchase_orders' => PurchaseOrder::query()
                ->where('status', 'partially_received')
                ->count(),
        ];
    }

    /**
     * @return array{
     *     labels: array<int, string>,
     *     sales_deliveries: array<int, int>,
     *     purchase_receipts: array<int, int>
     * }
     */
    public function operationsTrend(int $days = 7): array
    {
        $days = max(1, $days);
        $startDate = CarbonImmutable::today()->subDays($days - 1);
        $endDate = CarbonImmutable::today();

        $deliveryCountsByDate = SalesDelivery::query()
            ->selectRaw('delivery_date, COUNT(*) as aggregate')
            ->whereBetween('delivery_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('delivery_date')
            ->pluck('aggregate', 'delivery_date')
            ->map(fn ($count): int => (int) $count)
            ->all();

        $receiptCountsByDate = PurchaseReceipt::query()
            ->selectRaw('received_date, COUNT(*) as aggregate')
            ->whereBetween('received_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('received_date')
            ->pluck('aggregate', 'received_date')
            ->map(fn ($count): int => (int) $count)
            ->all();

        $labels = [];
        $salesDeliveries = [];
        $purchaseReceipts = [];

        for ($offset = 0; $offset < $days; $offset++) {
            $date = $startDate->addDays($offset);
            $dateKey = $date->toDateString();

            $labels[] = $date->format('M d');
            $salesDeliveries[] = $deliveryCountsByDate[$dateKey] ?? 0;
            $purchaseReceipts[] = $receiptCountsByDate[$dateKey] ?? 0;
        }

        return [
            'labels' => $labels,
            'sales_deliveries' => $salesDeliveries,
            'purchase_receipts' => $purchaseReceipts,
        ];
    }

    /**
     * @return array<int, int>
     */
    public function lowStockProductIds(): array
    {
        $latestStockRows = StockMovement::query()
            ->selectRaw('MAX(id) as id')
            ->groupBy('product_id', 'warehouse_id');

        $latestBalancesByProduct = DB::table('stock_movements as sm')
            ->joinSub($latestStockRows, 'latest_rows', function ($join): void {
                $join->on('sm.id', '=', 'latest_rows.id');
            })
            ->selectRaw('sm.product_id, SUM(sm.balance_after) as current_stock')
            ->groupBy('sm.product_id');

        return Product::query()
            ->leftJoinSub($latestBalancesByProduct, 'balances', function ($join): void {
                $join->on('products.id', '=', 'balances.product_id');
            })
            ->where('products.track_stock', true)
            ->where('products.is_active', true)
            ->where(function ($query): void {
                $query->whereRaw('COALESCE(balances.current_stock, 0) <= products.reorder_level');
            })
            ->pluck('products.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }
}
