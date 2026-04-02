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

        $this->addCheck('stock_movements', 'chk_sm_quantity_non_zero', '(quantity <> 0)');
        $this->addCheck('stock_movements', 'chk_sm_unit_cost_non_negative', '(unit_cost IS NULL OR unit_cost >= 0)');
        $this->addCheck('stock_movements', 'chk_sm_source_link_consistency', '((source_type IS NULL AND source_id IS NULL) OR (source_type IS NOT NULL AND source_id IS NOT NULL))');
    }

    public function down(): void
    {
        if (! $this->supportsCheckConstraints()) {
            return;
        }

        $this->dropCheck('stock_movements', 'chk_sm_source_link_consistency');
        $this->dropCheck('stock_movements', 'chk_sm_unit_cost_non_negative');
        $this->dropCheck('stock_movements', 'chk_sm_quantity_non_zero');
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

