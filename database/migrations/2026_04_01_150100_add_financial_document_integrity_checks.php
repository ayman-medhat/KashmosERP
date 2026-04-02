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

        $this->addCheck('sales_invoices', 'chk_si_status_valid', "status IN ('draft','posted','partially_paid','paid','cancelled')");
        $this->addCheck('sales_invoices', 'chk_si_posted_at_required', "((status IN ('draft','cancelled') AND posted_at IS NULL) OR (status IN ('posted','partially_paid','paid') AND posted_at IS NOT NULL))");
        $this->addCheck('sales_invoices', 'chk_si_paid_total_bounds', '(paid_total >= 0 AND paid_total <= grand_total)');
        $this->addCheck('sales_invoices', 'chk_si_paid_status', "((status = 'paid' AND paid_total = grand_total) OR (status <> 'paid'))");

        $this->addCheck('sales_invoice_items', 'chk_sii_qty', '(quantity > 0)');
        $this->addCheck('sales_invoice_items', 'chk_sii_amounts', '(line_subtotal >= 0 AND line_tax >= 0 AND line_total >= 0)');

        $this->addCheck('sales_receipts', 'chk_sr_status', "status IN ('confirmed')");
        $this->addCheck('sales_receipts', 'chk_sr_amount', '(amount > 0)');
        $this->addCheck('sales_receipts', 'chk_sr_dates', '(confirmed_at IS NOT NULL AND posted_at IS NOT NULL)');

        $this->addCheck('supplier_bills', 'chk_sb_status_valid', "status IN ('draft','posted','partially_paid','paid','cancelled')");
        $this->addCheck('supplier_bills', 'chk_sb_posted_at_required', "((status IN ('draft','cancelled') AND posted_at IS NULL) OR (status IN ('posted','partially_paid','paid') AND posted_at IS NOT NULL))");
        $this->addCheck('supplier_bills', 'chk_sb_paid_total_bounds', '(paid_total >= 0 AND paid_total <= grand_total)');
        $this->addCheck('supplier_bills', 'chk_sb_paid_status', "((status = 'paid' AND paid_total = grand_total) OR (status <> 'paid'))");

        $this->addCheck('supplier_bill_items', 'chk_sbi_qty', '(quantity > 0)');
        $this->addCheck('supplier_bill_items', 'chk_sbi_amounts', '(line_subtotal >= 0 AND line_tax >= 0 AND line_total >= 0)');

        $this->addCheck('supplier_payments', 'chk_sp_status', "status IN ('confirmed')");
        $this->addCheck('supplier_payments', 'chk_sp_amount', '(amount > 0)');
        $this->addCheck('supplier_payments', 'chk_sp_dates', '(confirmed_at IS NOT NULL AND posted_at IS NOT NULL)');
    }

    public function down(): void
    {
        if (! $this->supportsCheckConstraints()) {
            return;
        }

        $this->dropCheck('supplier_payments', 'chk_sp_dates');
        $this->dropCheck('supplier_payments', 'chk_sp_amount');
        $this->dropCheck('supplier_payments', 'chk_sp_status');
        $this->dropCheck('supplier_bill_items', 'chk_sbi_amounts');
        $this->dropCheck('supplier_bill_items', 'chk_sbi_qty');
        $this->dropCheck('supplier_bills', 'chk_sb_paid_status');
        $this->dropCheck('supplier_bills', 'chk_sb_paid_total_bounds');
        $this->dropCheck('supplier_bills', 'chk_sb_posted_at_required');
        $this->dropCheck('supplier_bills', 'chk_sb_status_valid');
        $this->dropCheck('sales_receipts', 'chk_sr_dates');
        $this->dropCheck('sales_receipts', 'chk_sr_amount');
        $this->dropCheck('sales_receipts', 'chk_sr_status');
        $this->dropCheck('sales_invoice_items', 'chk_sii_amounts');
        $this->dropCheck('sales_invoice_items', 'chk_sii_qty');
        $this->dropCheck('sales_invoices', 'chk_si_paid_status');
        $this->dropCheck('sales_invoices', 'chk_si_paid_total_bounds');
        $this->dropCheck('sales_invoices', 'chk_si_posted_at_required');
        $this->dropCheck('sales_invoices', 'chk_si_status_valid');
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

