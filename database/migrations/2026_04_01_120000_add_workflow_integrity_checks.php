<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->supportsCheckConstraints()) {
            return;
        }

        $this->addCheck('sales_orders', 'chk_so_status_valid', "status IN ('draft','submitted','approved','partially_delivered','delivered','cancelled')");
        $this->addCheck('sales_orders', 'chk_so_approved_at', "((status IN ('draft','submitted','cancelled') AND approved_at IS NULL) OR (status IN ('approved','partially_delivered','delivered') AND approved_at IS NOT NULL))");
        $this->addCheck('sales_orders', 'chk_so_posted_at', "(status NOT IN ('partially_delivered','delivered') OR posted_to_stock_at IS NOT NULL)");

        $this->addCheck('purchase_orders', 'chk_po_status_valid', "status IN ('draft','submitted','approved','partially_received','received','cancelled')");
        $this->addCheck('purchase_orders', 'chk_po_approved_at', "((status IN ('draft','submitted','cancelled') AND approved_at IS NULL) OR (status IN ('approved','partially_received','received') AND approved_at IS NOT NULL))");
        $this->addCheck('purchase_orders', 'chk_po_posted_at', "(status NOT IN ('partially_received','received') OR posted_to_stock_at IS NOT NULL)");

        $this->addCheck('sales_quotations', 'chk_sq_status_valid', "status IN ('draft','submitted','approved','converted','cancelled')");
        $this->addCheck('sales_quotations', 'chk_sq_approved_at', "((status IN ('draft','submitted','cancelled') AND approved_at IS NULL) OR (status IN ('approved','converted') AND approved_at IS NOT NULL))");
        // MySQL does not allow CHECK expressions that reference FK columns with referential actions.
        // Keep DB-level state consistency on converted_at; converted_sales_order_id integrity is handled by FK + workflow services.
        $this->addCheck('sales_quotations', 'chk_sq_converted', "((status <> 'converted' AND converted_at IS NULL) OR (status = 'converted' AND converted_at IS NOT NULL))");

        $this->addCheck('sales_order_items', 'chk_soi_qty', '(quantity > 0 AND delivered_qty >= 0 AND delivered_qty <= quantity)');
        $this->addCheck('purchase_order_items', 'chk_poi_qty', '(quantity > 0 AND received_qty >= 0 AND received_qty <= quantity)');
        $this->addCheck('sales_quotation_items', 'chk_sqi_qty', '(quantity > 0)');

        $this->addCheck('sales_deliveries', 'chk_sd_status', "status IN ('confirmed')");
        $this->addCheck('purchase_receipts', 'chk_pr_status', "status IN ('confirmed')");

        $this->addCheck('sales_delivery_items', 'chk_sdi_qty', '(ordered_qty > 0 AND delivered_qty > 0 AND delivered_qty <= ordered_qty)');
        $this->addCheck('purchase_receipt_items', 'chk_pri_qty', '(ordered_qty > 0 AND received_qty > 0 AND received_qty <= ordered_qty)');
    }

    public function down(): void
    {
        if (! $this->supportsCheckConstraints()) {
            return;
        }

        $this->dropCheck('purchase_receipt_items', 'chk_pri_qty');
        $this->dropCheck('sales_delivery_items', 'chk_sdi_qty');
        $this->dropCheck('purchase_receipts', 'chk_pr_status');
        $this->dropCheck('sales_deliveries', 'chk_sd_status');
        $this->dropCheck('sales_quotation_items', 'chk_sqi_qty');
        $this->dropCheck('purchase_order_items', 'chk_poi_qty');
        $this->dropCheck('sales_order_items', 'chk_soi_qty');
        $this->dropCheck('sales_quotations', 'chk_sq_converted');
        $this->dropCheck('sales_quotations', 'chk_sq_approved_at');
        $this->dropCheck('sales_quotations', 'chk_sq_status_valid');
        $this->dropCheck('purchase_orders', 'chk_po_posted_at');
        $this->dropCheck('purchase_orders', 'chk_po_approved_at');
        $this->dropCheck('purchase_orders', 'chk_po_status_valid');
        $this->dropCheck('sales_orders', 'chk_so_posted_at');
        $this->dropCheck('sales_orders', 'chk_so_approved_at');
        $this->dropCheck('sales_orders', 'chk_so_status_valid');
    }

    private function supportsCheckConstraints(): bool
    {
        return in_array(DB::getDriverName(), ['mysql', 'pgsql'], true);
    }

    private function addCheck(string $table, string $constraint, string $expression): void
    {
        if ($this->checkConstraintExists($table, $constraint)) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(sprintf(
                'ALTER TABLE "%s" ADD CONSTRAINT "%s" CHECK (%s)',
                $table,
                $constraint,
                $expression
            ));

            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD CONSTRAINT `%s` CHECK (%s)',
            $table,
            $constraint,
            $expression
        ));
    }

    private function dropCheck(string $table, string $constraint): void
    {
        if (! $this->checkConstraintExists($table, $constraint)) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(sprintf(
                'ALTER TABLE "%s" DROP CONSTRAINT "%s"',
                $table,
                $constraint
            ));

            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` DROP CHECK `%s`',
            $table,
            $constraint
        ));
    }

    private function checkConstraintExists(string $table, string $constraint): bool
    {
        if (DB::getDriverName() === 'pgsql') {
            return DB::table('pg_constraint as c')
                ->join('pg_class as t', 'c.conrelid', '=', 't.oid')
                ->join('pg_namespace as n', 't.relnamespace', '=', 'n.oid')
                ->where('c.conname', $constraint)
                ->where('t.relname', $table)
                ->whereRaw('n.nspname = current_schema()')
                ->exists();
        }

        return DB::table('information_schema.table_constraints')
            ->where('constraint_schema', DB::raw('DATABASE()'))
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->where('constraint_type', 'CHECK')
            ->exists();
    }
};
