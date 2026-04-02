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

        $this->addCheck('chart_of_accounts', 'chk_coa_account_type_valid', "account_type IN ('asset','liability','equity','revenue','expense')");
        $this->addCheck('chart_of_accounts', 'chk_coa_normal_balance_valid', "normal_balance IN ('debit','credit')");

        $this->addCheck('journal_entries', 'chk_je_status_valid', "status IN ('draft','posted','reversed')");
        $this->addCheck('journal_entries', 'chk_je_posted_at_required', "((status = 'draft' AND posted_at IS NULL) OR (status IN ('posted','reversed') AND posted_at IS NOT NULL))");
        $this->addCheck('journal_entries', 'chk_je_totals_non_negative', '(total_debit >= 0 AND total_credit >= 0)');
        $this->addCheck('journal_entries', 'chk_je_totals_when_posted', "(status = 'draft' OR (total_debit > 0 AND total_credit > 0 AND total_debit = total_credit))");
        $this->addCheck('journal_entries', 'chk_je_source_link_consistency', '((source_type IS NULL AND source_id IS NULL) OR (source_type IS NOT NULL AND source_id IS NOT NULL))');

        $this->addCheck('journal_lines', 'chk_jl_amounts_non_negative', '(debit >= 0 AND credit >= 0)');
        $this->addCheck('journal_lines', 'chk_jl_single_sided', '((debit > 0 AND credit = 0) OR (credit > 0 AND debit = 0))');
    }

    public function down(): void
    {
        if (! $this->supportsCheckConstraints()) {
            return;
        }

        $this->dropCheck('journal_lines', 'chk_jl_single_sided');
        $this->dropCheck('journal_lines', 'chk_jl_amounts_non_negative');
        $this->dropCheck('journal_entries', 'chk_je_source_link_consistency');
        $this->dropCheck('journal_entries', 'chk_je_totals_when_posted');
        $this->dropCheck('journal_entries', 'chk_je_totals_non_negative');
        $this->dropCheck('journal_entries', 'chk_je_posted_at_required');
        $this->dropCheck('journal_entries', 'chk_je_status_valid');
        $this->dropCheck('chart_of_accounts', 'chk_coa_normal_balance_valid');
        $this->dropCheck('chart_of_accounts', 'chk_coa_account_type_valid');
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

