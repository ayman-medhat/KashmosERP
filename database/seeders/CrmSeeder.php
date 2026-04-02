<?php

namespace Database\Seeders;

use App\Core\Models\CompanyProfile;
use App\Core\Models\Role;
use App\Core\Models\User;
use App\Modules\CRM\Models\CrmAccount;
use App\Modules\CRM\Models\CrmActivity;
use App\Modules\CRM\Models\CrmAssignmentRule;
use App\Modules\CRM\Models\CrmContact;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmLeadSource;
use App\Modules\CRM\Models\CrmNote;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CrmSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'kashmos@outlook.com')->first()
            ?? User::query()->where('is_active', true)->first()
            ?? User::query()->first();

        $companyProfile = CompanyProfile::query()->first();
        $companyProfileId = $companyProfile?->id;
        $actorId = $user?->id;

        $this->seedRoles();

        $website = CrmLeadSource::query()->updateOrCreate(
            ['code' => 'WEBSITE'],
            [
                'uuid' => (string) Str::uuid(),
                'name_translations' => ['en' => 'Website', 'ar' => 'الموقع الإلكتروني'],
                'is_active' => true,
                'is_system' => true,
                'created_by' => $actorId,
            ],
        );

        $referral = CrmLeadSource::query()->updateOrCreate(
            ['code' => 'REFERRAL'],
            [
                'uuid' => (string) Str::uuid(),
                'name_translations' => ['en' => 'Referral', 'ar' => 'الإحالات'],
                'is_active' => true,
                'is_system' => true,
                'created_by' => $actorId,
            ],
        );

        $campaign = CrmLeadSource::query()->updateOrCreate(
            ['code' => 'CAMPAIGN'],
            [
                'uuid' => (string) Str::uuid(),
                'name_translations' => ['en' => 'Campaign', 'ar' => 'الحملات'],
                'is_active' => true,
                'is_system' => true,
                'created_by' => $actorId,
            ],
        );

        $direct = CrmLeadSource::query()->updateOrCreate(
            ['code' => 'DIRECT'],
            [
                'uuid' => (string) Str::uuid(),
                'name_translations' => ['en' => 'Direct Sales', 'ar' => 'مبيعات مباشرة'],
                'is_active' => true,
                'is_system' => true,
                'created_by' => $actorId,
            ],
        );

        $newStage = CrmPipelineStage::query()->updateOrCreate(
            ['code' => 'NEW'],
            [
                'uuid' => (string) Str::uuid(),
                'name_translations' => ['en' => 'New', 'ar' => 'جديد'],
                'stage_order' => 1,
                'color' => '#0EA5E9',
                'default_probability' => 10,
                'is_won_stage' => false,
                'is_lost_stage' => false,
                'is_active' => true,
                'is_system' => true,
                'created_by' => $actorId,
            ],
        );

        $qualifiedStage = CrmPipelineStage::query()->updateOrCreate(
            ['code' => 'QUALIFIED'],
            [
                'uuid' => (string) Str::uuid(),
                'name_translations' => ['en' => 'Qualified', 'ar' => 'مؤهل'],
                'stage_order' => 2,
                'color' => '#F59E0B',
                'default_probability' => 40,
                'is_won_stage' => false,
                'is_lost_stage' => false,
                'is_active' => true,
                'is_system' => true,
                'created_by' => $actorId,
            ],
        );

        $proposalStage = CrmPipelineStage::query()->updateOrCreate(
            ['code' => 'PROPOSAL'],
            [
                'uuid' => (string) Str::uuid(),
                'name_translations' => ['en' => 'Proposal', 'ar' => 'عرض'],
                'stage_order' => 3,
                'color' => '#6366F1',
                'default_probability' => 65,
                'is_won_stage' => false,
                'is_lost_stage' => false,
                'is_active' => true,
                'is_system' => true,
                'created_by' => $actorId,
            ],
        );

        $wonStage = CrmPipelineStage::query()->updateOrCreate(
            ['code' => 'WON'],
            [
                'uuid' => (string) Str::uuid(),
                'name_translations' => ['en' => 'Won', 'ar' => 'رابحة'],
                'stage_order' => 4,
                'color' => '#10B981',
                'default_probability' => 100,
                'is_won_stage' => true,
                'is_lost_stage' => false,
                'is_active' => true,
                'is_system' => true,
                'created_by' => $actorId,
            ],
        );

        $lostStage = CrmPipelineStage::query()->updateOrCreate(
            ['code' => 'LOST'],
            [
                'uuid' => (string) Str::uuid(),
                'name_translations' => ['en' => 'Lost', 'ar' => 'خاسرة'],
                'stage_order' => 5,
                'color' => '#EF4444',
                'default_probability' => 0,
                'is_won_stage' => false,
                'is_lost_stage' => true,
                'is_active' => true,
                'is_system' => true,
                'created_by' => $actorId,
            ],
        );

        $accountA = CrmAccount::query()->updateOrCreate(
            ['code' => 'ACC-1001'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'name_translations' => ['en' => 'Nile Distribution Group', 'ar' => 'مجموعة النيل للتوزيع'],
                'industry' => 'Retail',
                'website' => 'https://nile-distribution.test',
                'email' => 'procurement@nile-distribution.test',
                'phone' => '+20-2-2000-1100',
                'address_translations' => ['en' => 'Cairo, Egypt', 'ar' => 'القاهرة، مصر'],
                'owner_id' => $actorId,
                'assigned_by' => $actorId,
                'last_activity_at' => now()->subDays(1),
                'next_follow_up_at' => now()->addDays(3),
                'is_active' => true,
                'created_by' => $actorId,
            ],
        );

        $accountB = CrmAccount::query()->updateOrCreate(
            ['code' => 'ACC-1002'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'name_translations' => ['en' => 'Delta Industrial Supplies', 'ar' => 'مستلزمات دلتا الصناعية'],
                'industry' => 'Manufacturing',
                'website' => 'https://delta-industrial.test',
                'email' => 'sales@delta-industrial.test',
                'phone' => '+20-2-2000-2200',
                'address_translations' => ['en' => 'Giza, Egypt', 'ar' => 'الجيزة، مصر'],
                'owner_id' => $actorId,
                'assigned_by' => $actorId,
                'last_activity_at' => now()->subDays(2),
                'next_follow_up_at' => now()->addDays(5),
                'is_active' => true,
                'created_by' => $actorId,
            ],
        );

        $accountC = CrmAccount::query()->updateOrCreate(
            ['code' => 'ACC-1003'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'name_translations' => ['en' => 'Alexandria Hotels Alliance', 'ar' => 'تحالف فنادق الإسكندرية'],
                'industry' => 'Hospitality',
                'website' => 'https://alex-hotels.test',
                'email' => 'operations@alex-hotels.test',
                'phone' => '+20-3-3000-3300',
                'address_translations' => ['en' => 'Alexandria, Egypt', 'ar' => 'الإسكندرية، مصر'],
                'owner_id' => $actorId,
                'assigned_by' => $actorId,
                'last_activity_at' => now()->subDays(4),
                'next_follow_up_at' => now()->addDays(7),
                'is_active' => true,
                'created_by' => $actorId,
            ],
        );

        $contactA = CrmContact::query()->updateOrCreate(
            ['email' => 'omar.hassan@nile-distribution.test'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'crm_account_id' => $accountA->id,
                'first_name' => 'Omar',
                'last_name' => 'Hassan',
                'job_title' => 'Procurement Manager',
                'phone' => '+20-100-000-1101',
                'address_translations' => ['en' => 'Cairo', 'ar' => 'القاهرة'],
                'owner_id' => $actorId,
                'assigned_by' => $actorId,
                'last_activity_at' => now()->subDays(1),
                'next_follow_up_at' => now()->addDays(2),
                'is_active' => true,
                'created_by' => $actorId,
            ],
        );

        $contactB = CrmContact::query()->updateOrCreate(
            ['email' => 'mona.fathy@delta-industrial.test'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'crm_account_id' => $accountB->id,
                'first_name' => 'Mona',
                'last_name' => 'Fathy',
                'job_title' => 'Commercial Director',
                'phone' => '+20-100-000-2202',
                'address_translations' => ['en' => 'Giza', 'ar' => 'الجيزة'],
                'owner_id' => $actorId,
                'assigned_by' => $actorId,
                'last_activity_at' => now()->subDays(3),
                'next_follow_up_at' => now()->addDays(4),
                'is_active' => true,
                'created_by' => $actorId,
            ],
        );

        $contactC = CrmContact::query()->updateOrCreate(
            ['email' => 'ahmed.kamal@alex-hotels.test'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'crm_account_id' => $accountC->id,
                'first_name' => 'Ahmed',
                'last_name' => 'Kamal',
                'job_title' => 'Operations Head',
                'phone' => '+20-100-000-3303',
                'address_translations' => ['en' => 'Alexandria', 'ar' => 'الإسكندرية'],
                'owner_id' => $actorId,
                'assigned_by' => $actorId,
                'last_activity_at' => now()->subDays(5),
                'next_follow_up_at' => now()->addDays(6),
                'is_active' => true,
                'created_by' => $actorId,
            ],
        );

        $leadA = CrmLead::query()->updateOrCreate(
            ['lead_no' => 'LEAD-000001'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'name' => 'Nile Distribution Expansion Deal',
                'email' => $contactA->email,
                'phone' => $contactA->phone,
                'crm_account_id' => $accountA->id,
                'crm_contact_id' => $contactA->id,
                'crm_lead_source_id' => $website->id,
                'status' => 'qualified',
                'expected_value' => 150000,
                'expected_close_date' => now()->addWeeks(6)->toDateString(),
                'qualified_at' => now()->subDays(2),
                'last_activity_at' => now()->subDay(),
                'next_follow_up_at' => now()->addDays(2),
                'details' => ['note' => 'Seeking annual supply framework.'],
                'owner_id' => $actorId,
                'assigned_by' => $actorId,
                'created_by' => $actorId,
            ],
        );

        $leadB = CrmLead::query()->updateOrCreate(
            ['lead_no' => 'LEAD-000002'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'name' => 'Delta Warehouse Automation',
                'email' => $contactB->email,
                'phone' => $contactB->phone,
                'crm_account_id' => $accountB->id,
                'crm_contact_id' => $contactB->id,
                'crm_lead_source_id' => $campaign->id,
                'status' => 'new',
                'expected_value' => 82000,
                'expected_close_date' => now()->addWeeks(8)->toDateString(),
                'last_activity_at' => now()->subDays(2),
                'next_follow_up_at' => now()->addDays(1),
                'details' => ['note' => 'Requested product and service bundle quote.'],
                'owner_id' => $actorId,
                'assigned_by' => $actorId,
                'created_by' => $actorId,
            ],
        );

        $leadC = CrmLead::query()->updateOrCreate(
            ['lead_no' => 'LEAD-000003'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'name' => 'Alex Hotels Procurement Renewal',
                'email' => $contactC->email,
                'phone' => $contactC->phone,
                'crm_account_id' => $accountC->id,
                'crm_contact_id' => $contactC->id,
                'crm_lead_source_id' => $referral->id,
                'status' => 'new',
                'expected_value' => 60000,
                'expected_close_date' => now()->addWeeks(4)->toDateString(),
                'last_activity_at' => now()->subDays(3),
                'next_follow_up_at' => now()->addDays(3),
                'details' => ['note' => 'Considering switching suppliers before summer season.'],
                'owner_id' => $actorId,
                'assigned_by' => $actorId,
                'created_by' => $actorId,
            ],
        );

        $opportunityA = CrmOpportunity::query()->updateOrCreate(
            ['opportunity_no' => 'OPP-000001'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'name' => 'Nile Distribution Annual Contract',
                'crm_account_id' => $accountA->id,
                'crm_contact_id' => $contactA->id,
                'crm_lead_id' => $leadA->id,
                'crm_pipeline_stage_id' => $proposalStage->id,
                'status' => 'open',
                'probability' => $proposalStage->default_probability,
                'expected_value' => 150000,
                'expected_close_date' => now()->addWeeks(4)->toDateString(),
                'last_activity_at' => now()->subDay(),
                'next_follow_up_at' => now()->addDays(2),
                'details' => ['note' => 'Commercial proposal shared; waiting final comments.'],
                'owner_id' => $actorId,
                'assigned_by' => $actorId,
                'created_by' => $actorId,
            ],
        );

        $opportunityB = CrmOpportunity::query()->updateOrCreate(
            ['opportunity_no' => 'OPP-000002'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'name' => 'Alex Hotels Multi-Branch Supply',
                'crm_account_id' => $accountC->id,
                'crm_contact_id' => $contactC->id,
                'crm_lead_id' => $leadC->id,
                'crm_pipeline_stage_id' => $qualifiedStage->id,
                'status' => 'open',
                'probability' => $qualifiedStage->default_probability,
                'expected_value' => 60000,
                'expected_close_date' => now()->addWeeks(5)->toDateString(),
                'last_activity_at' => now()->subDays(2),
                'next_follow_up_at' => now()->addDays(4),
                'details' => ['note' => 'Needs approvals from central procurement team.'],
                'owner_id' => $actorId,
                'assigned_by' => $actorId,
                'created_by' => $actorId,
            ],
        );

        $leadA->forceFill([
            'status' => 'converted',
            'converted_at' => now()->subDay(),
            'converted_crm_opportunity_id' => $opportunityA->id,
        ])->saveQuietly();

        $opportunityA->stageHistory()->updateOrCreate(
            [
                'crm_opportunity_id' => $opportunityA->id,
                'to_crm_pipeline_stage_id' => $proposalStage->id,
            ],
            [
                'from_crm_pipeline_stage_id' => $newStage->id,
                'from_probability' => $newStage->default_probability,
                'to_probability' => $proposalStage->default_probability,
                'note' => 'Moved after proposal submission.',
                'changed_by' => $actorId,
                'changed_at' => now()->subDay(),
            ],
        );

        CrmActivity::query()->updateOrCreate(
            ['title' => 'Discovery call with Nile Distribution'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'subject_type' => CrmLead::class,
                'subject_id' => $leadB->id,
                'crm_contact_id' => $contactB->id,
                'activity_type' => 'call',
                'status' => 'scheduled',
                'priority' => 'high',
                'due_at' => now()->addDay(),
                'details' => ['agenda' => 'Discuss integration scope and timeline.'],
                'owner_id' => $actorId,
                'created_by' => $actorId,
            ],
        );

        CrmActivity::query()->updateOrCreate(
            ['title' => 'Proposal review meeting'],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'subject_type' => CrmOpportunity::class,
                'subject_id' => $opportunityA->id,
                'crm_contact_id' => $contactA->id,
                'activity_type' => 'meeting',
                'status' => 'completed',
                'priority' => 'normal',
                'due_at' => now()->subDay(),
                'completed_at' => now()->subDay(),
                'details' => ['outcome' => 'Client requested final pricing revision.'],
                'owner_id' => $actorId,
                'created_by' => $actorId,
            ],
        );

        CrmNote::query()->updateOrCreate(
            [
                'subject_type' => CrmOpportunity::class,
                'subject_id' => $opportunityA->id,
            ],
            [
                'uuid' => (string) Str::uuid(),
                'company_profile_id' => $companyProfileId,
                'note' => 'Client requested delivery split over two months with fixed pricing.',
                'visibility' => 'internal',
                'created_by' => $actorId,
            ],
        );

        CrmAssignmentRule::query()->updateOrCreate(
            ['name' => 'Website Leads - Round Robin'],
            [
                'uuid' => (string) Str::uuid(),
                'entity_type' => 'lead',
                'priority' => 10,
                'is_active' => true,
                'conditions' => ['crm_lead_source_id' => $website->id],
                'assignment_strategy' => 'round_robin',
                'assigned_user_ids' => $actorId ? [$actorId] : [],
                'created_by' => $actorId,
            ],
        );

        CrmAssignmentRule::query()->updateOrCreate(
            ['name' => 'Direct Opportunities - Manual'],
            [
                'uuid' => (string) Str::uuid(),
                'entity_type' => 'opportunity',
                'priority' => 20,
                'is_active' => true,
                'conditions' => ['crm_lead_source_id' => $direct->id],
                'assignment_strategy' => 'manual',
                'assigned_user_ids' => $actorId ? [$actorId] : [],
                'created_by' => $actorId,
            ],
        );

        CrmPipelineStage::query()->whereKey($wonStage->id)->update(['is_active' => true]);
        CrmPipelineStage::query()->whereKey($lostStage->id)->update(['is_active' => true]);
    }

    private function seedRoles(): void
    {
        $allCrmPermissions = [
            'crm.view',
            'crm.create',
            'crm.edit',
            'crm.delete',
            'crm.export',
            'crm.assign',
            'crm.convert_lead',
            'crm.move_stage',
            'crm.complete_activity',
            'crm.manage_pipeline',
            'crm.manage_sources',
            'crm.manage_rules',
            'crm.view_reports',
        ];

        Role::query()->updateOrCreate(
            ['name' => 'crm-admin', 'guard_name' => 'web'],
            ['display_name' => 'CRM Admin', 'is_system' => true],
        )->syncPermissions($allCrmPermissions);

        Role::query()->updateOrCreate(
            ['name' => 'sales-manager', 'guard_name' => 'web'],
            ['display_name' => 'Sales Manager', 'is_system' => true],
        )->syncPermissions([
            'crm.view',
            'crm.create',
            'crm.edit',
            'crm.export',
            'crm.assign',
            'crm.convert_lead',
            'crm.move_stage',
            'crm.complete_activity',
            'crm.view_reports',
        ]);

        Role::query()->updateOrCreate(
            ['name' => 'sales-rep', 'guard_name' => 'web'],
            ['display_name' => 'Sales Rep', 'is_system' => true],
        )->syncPermissions([
            'crm.view',
            'crm.create',
            'crm.edit',
            'crm.convert_lead',
            'crm.move_stage',
            'crm.complete_activity',
        ]);

        Role::query()->updateOrCreate(
            ['name' => 'sales-assistant', 'guard_name' => 'web'],
            ['display_name' => 'Sales Assistant', 'is_system' => true],
        )->syncPermissions([
            'crm.view',
            'crm.create',
            'crm.edit',
            'crm.complete_activity',
        ]);

        Role::query()->updateOrCreate(
            ['name' => 'viewer', 'guard_name' => 'web'],
            ['display_name' => 'Viewer', 'is_system' => true],
        )->syncPermissions([
            'crm.view',
            'crm.view_reports',
        ]);
    }
}
