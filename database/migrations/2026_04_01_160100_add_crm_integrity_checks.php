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

        $this->addCheck('crm_pipeline_stages', 'chk_crm_stage_probability', '(default_probability >= 0 AND default_probability <= 100)');
        $this->addCheck('crm_pipeline_stages', 'chk_crm_stage_flags', '(NOT (is_won_stage AND is_lost_stage))');

        $this->addCheck('crm_leads', 'chk_crm_lead_status', "status IN ('new','qualified','disqualified','converted')");
        $this->addCheck('crm_leads', 'chk_crm_lead_expected_value', '(expected_value IS NULL OR expected_value >= 0)');
        $this->addCheck('crm_leads', 'chk_crm_lead_converted', "((status = 'converted' AND converted_at IS NOT NULL) OR (status <> 'converted' AND converted_at IS NULL))");

        $this->addCheck('crm_opportunities', 'chk_crm_opp_status', "status IN ('open','won','lost')");
        $this->addCheck('crm_opportunities', 'chk_crm_opp_probability', '(probability >= 0 AND probability <= 100)');
        $this->addCheck('crm_opportunities', 'chk_crm_opp_expected_value', '(expected_value >= 0)');
        $this->addCheck('crm_opportunities', 'chk_crm_opp_outcome_dates', "((status = 'won' AND won_at IS NOT NULL) OR (status <> 'won' AND won_at IS NULL)) AND ((status = 'lost' AND lost_at IS NOT NULL) OR (status <> 'lost' AND lost_at IS NULL))");

        $this->addCheck('crm_activities', 'chk_crm_activity_status', "status IN ('scheduled','completed','overdue','canceled')");
        $this->addCheck('crm_activities', 'chk_crm_activity_completed', "((status = 'completed' AND completed_at IS NOT NULL) OR (status <> 'completed' AND completed_at IS NULL))");
        $this->addCheck('crm_activities', 'chk_crm_activity_priority', "priority IN ('low','normal','high','urgent')");

        $this->addCheck('crm_tasks', 'chk_crm_task_status', "status IN ('open','in_progress','completed','canceled')");
        $this->addCheck('crm_tasks', 'chk_crm_task_completed', "((status = 'completed' AND completed_at IS NOT NULL) OR (status <> 'completed' AND completed_at IS NULL))");
        $this->addCheck('crm_tasks', 'chk_crm_task_priority', "priority IN ('low','normal','high','urgent')");

        $this->addCheck('crm_notes', 'chk_crm_note_visibility', "visibility IN ('internal','external')");
        $this->addCheck('crm_emails', 'chk_crm_email_direction', "direction IN ('inbound','outbound')");
        $this->addCheck('crm_emails', 'chk_crm_email_status', "status IN ('draft','sent','received','failed')");
        $this->addCheck('crm_calls', 'chk_crm_call_direction', "direction IN ('inbound','outbound')");
        $this->addCheck('crm_calls', 'chk_crm_call_status', "status IN ('scheduled','completed','missed','canceled')");
        $this->addCheck('crm_calls', 'chk_crm_call_duration', '(duration_seconds >= 0)');

        $this->addCheck('crm_assignment_rules', 'chk_crm_rule_entity', "entity_type IN ('lead','opportunity')");
        $this->addCheck('crm_assignment_rules', 'chk_crm_rule_strategy', "assignment_strategy IN ('round_robin','least_loaded','manual')");
        $this->addCheck('crm_assignment_rules', 'chk_crm_rule_priority', '(priority >= 1)');

        $this->addCheck('crm_stage_history', 'chk_crm_stage_history_prob', '(from_probability IS NULL OR (from_probability >= 0 AND from_probability <= 100)) AND (to_probability IS NULL OR (to_probability >= 0 AND to_probability <= 100))');
    }

    public function down(): void
    {
        if (! $this->supportsCheckConstraints()) {
            return;
        }

        $this->dropCheck('crm_stage_history', 'chk_crm_stage_history_prob');
        $this->dropCheck('crm_assignment_rules', 'chk_crm_rule_priority');
        $this->dropCheck('crm_assignment_rules', 'chk_crm_rule_strategy');
        $this->dropCheck('crm_assignment_rules', 'chk_crm_rule_entity');
        $this->dropCheck('crm_calls', 'chk_crm_call_duration');
        $this->dropCheck('crm_calls', 'chk_crm_call_status');
        $this->dropCheck('crm_calls', 'chk_crm_call_direction');
        $this->dropCheck('crm_emails', 'chk_crm_email_status');
        $this->dropCheck('crm_emails', 'chk_crm_email_direction');
        $this->dropCheck('crm_notes', 'chk_crm_note_visibility');
        $this->dropCheck('crm_tasks', 'chk_crm_task_priority');
        $this->dropCheck('crm_tasks', 'chk_crm_task_completed');
        $this->dropCheck('crm_tasks', 'chk_crm_task_status');
        $this->dropCheck('crm_activities', 'chk_crm_activity_priority');
        $this->dropCheck('crm_activities', 'chk_crm_activity_completed');
        $this->dropCheck('crm_activities', 'chk_crm_activity_status');
        $this->dropCheck('crm_opportunities', 'chk_crm_opp_outcome_dates');
        $this->dropCheck('crm_opportunities', 'chk_crm_opp_expected_value');
        $this->dropCheck('crm_opportunities', 'chk_crm_opp_probability');
        $this->dropCheck('crm_opportunities', 'chk_crm_opp_status');
        $this->dropCheck('crm_leads', 'chk_crm_lead_converted');
        $this->dropCheck('crm_leads', 'chk_crm_lead_expected_value');
        $this->dropCheck('crm_leads', 'chk_crm_lead_status');
        $this->dropCheck('crm_pipeline_stages', 'chk_crm_stage_flags');
        $this->dropCheck('crm_pipeline_stages', 'chk_crm_stage_probability');
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
            DB::statement(sprintf('ALTER TABLE "%s" ADD CONSTRAINT "%s" CHECK (%s)', $table, $constraint, $expression));

            return;
        }

        DB::statement(sprintf('ALTER TABLE `%s` ADD CONSTRAINT `%s` CHECK (%s)', $table, $constraint, $expression));
    }

    private function dropCheck(string $table, string $constraint): void
    {
        if (! $this->checkConstraintExists($table, $constraint)) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement(sprintf('ALTER TABLE "%s" DROP CONSTRAINT "%s"', $table, $constraint));

            return;
        }

        DB::statement(sprintf('ALTER TABLE `%s` DROP CHECK `%s`', $table, $constraint));
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
