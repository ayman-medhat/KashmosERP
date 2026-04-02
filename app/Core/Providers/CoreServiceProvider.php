<?php

namespace App\Core\Providers;

use App\Core\Models\CompanyProfile;
use App\Core\Models\Permission;
use App\Core\Models\Role;
use App\Core\Models\Setting;
use App\Core\Models\ThemePreset;
use App\Core\Models\User;
use App\Core\Models\UserPreference;
use App\Core\Observers\AuditableObserver;
use App\Core\Policies\AuditLogPolicy;
use App\Core\Policies\PermissionPolicy;
use App\Core\Policies\RolePolicy;
use App\Core\Policies\UserPolicy;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\ProductCategory;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Tax;
use App\Modules\MasterData\Models\Unit;
use App\Modules\MasterData\Models\Warehouse;
use App\Modules\MasterData\Policies\CustomerPolicy;
use App\Modules\MasterData\Policies\ProductCategoryPolicy;
use App\Modules\MasterData\Policies\ProductPolicy;
use App\Modules\MasterData\Policies\SupplierPolicy;
use App\Modules\MasterData\Policies\TaxPolicy;
use App\Modules\MasterData\Policies\UnitPolicy;
use App\Modules\MasterData\Policies\WarehousePolicy;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Inventory\Policies\StockMovementPolicy;
use App\Modules\Accounting\Models\ChartOfAccount;
use App\Modules\Accounting\Models\JournalEntry;
use App\Modules\Accounting\Models\JournalLine;
use App\Modules\Accounting\Policies\ChartOfAccountPolicy;
use App\Modules\Accounting\Policies\JournalEntryPolicy;
use App\Modules\Sales\Models\SalesOrder;
use App\Modules\Sales\Models\SalesQuotation;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Sales\Models\SalesInvoiceItem;
use App\Modules\Sales\Models\SalesReceipt;
use App\Modules\Sales\Policies\SalesOrderPolicy;
use App\Modules\Sales\Policies\SalesQuotationPolicy;
use App\Modules\Sales\Policies\SalesDeliveryPolicy;
use App\Modules\Sales\Policies\SalesInvoicePolicy;
use App\Modules\Sales\Policies\SalesReceiptPolicy;
use App\Modules\Purchasing\Models\PurchaseOrder;
use App\Modules\Purchasing\Models\PurchaseReceipt;
use App\Modules\Purchasing\Models\SupplierBill;
use App\Modules\Purchasing\Models\SupplierBillItem;
use App\Modules\Purchasing\Models\SupplierPayment;
use App\Modules\Purchasing\Policies\PurchaseOrderPolicy;
use App\Modules\Purchasing\Policies\PurchaseReceiptPolicy;
use App\Modules\Purchasing\Policies\SupplierBillPolicy;
use App\Modules\Purchasing\Policies\SupplierPaymentPolicy;
use App\Modules\CRM\Models\CrmAccount;
use App\Modules\CRM\Models\CrmActivity;
use App\Modules\CRM\Models\CrmActivityLog;
use App\Modules\CRM\Models\CrmAssignmentRule;
use App\Modules\CRM\Models\CrmAttachment;
use App\Modules\CRM\Models\CrmCall;
use App\Modules\CRM\Models\CrmContact;
use App\Modules\CRM\Models\CrmEmail;
use App\Modules\CRM\Models\CrmLead;
use App\Modules\CRM\Models\CrmLeadSource;
use App\Modules\CRM\Models\CrmNote;
use App\Modules\CRM\Models\CrmOpportunity;
use App\Modules\CRM\Models\CrmPipelineStage;
use App\Modules\CRM\Models\CrmStageHistory;
use App\Modules\CRM\Models\CrmTag;
use App\Modules\CRM\Models\CrmTask;
use App\Modules\CRM\Policies\CrmAccountPolicy;
use App\Modules\CRM\Policies\CrmActivityPolicy;
use App\Modules\CRM\Policies\CrmAssignmentRulePolicy;
use App\Modules\CRM\Policies\CrmAttachmentPolicy;
use App\Modules\CRM\Policies\CrmCallPolicy;
use App\Modules\CRM\Policies\CrmContactPolicy;
use App\Modules\CRM\Policies\CrmEmailPolicy;
use App\Modules\CRM\Policies\CrmLeadPolicy;
use App\Modules\CRM\Policies\CrmLeadSourcePolicy;
use App\Modules\CRM\Policies\CrmNotePolicy;
use App\Modules\CRM\Policies\CrmOpportunityPolicy;
use App\Modules\CRM\Policies\CrmPipelineStagePolicy;
use App\Modules\CRM\Policies\CrmTaskPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class CoreServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(Permission::class, PermissionPolicy::class);
        Gate::policy(\App\Core\Models\AuditLog::class, AuditLogPolicy::class);
        Gate::policy(ProductCategory::class, ProductCategoryPolicy::class);
        Gate::policy(Unit::class, UnitPolicy::class);
        Gate::policy(Tax::class, TaxPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(Warehouse::class, WarehousePolicy::class);
        Gate::policy(StockMovement::class, StockMovementPolicy::class);
        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);
        Gate::policy(SalesQuotation::class, SalesQuotationPolicy::class);
        Gate::policy(SalesDelivery::class, SalesDeliveryPolicy::class);
        Gate::policy(SalesInvoice::class, SalesInvoicePolicy::class);
        Gate::policy(SalesReceipt::class, SalesReceiptPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(PurchaseReceipt::class, PurchaseReceiptPolicy::class);
        Gate::policy(SupplierBill::class, SupplierBillPolicy::class);
        Gate::policy(SupplierPayment::class, SupplierPaymentPolicy::class);
        Gate::policy(ChartOfAccount::class, ChartOfAccountPolicy::class);
        Gate::policy(JournalEntry::class, JournalEntryPolicy::class);
        Gate::policy(CrmAccount::class, CrmAccountPolicy::class);
        Gate::policy(CrmContact::class, CrmContactPolicy::class);
        Gate::policy(CrmLead::class, CrmLeadPolicy::class);
        Gate::policy(CrmOpportunity::class, CrmOpportunityPolicy::class);
        Gate::policy(CrmActivity::class, CrmActivityPolicy::class);
        Gate::policy(CrmTask::class, CrmTaskPolicy::class);
        Gate::policy(CrmNote::class, CrmNotePolicy::class);
        Gate::policy(CrmAttachment::class, CrmAttachmentPolicy::class);
        Gate::policy(CrmEmail::class, CrmEmailPolicy::class);
        Gate::policy(CrmCall::class, CrmCallPolicy::class);
        Gate::policy(CrmLeadSource::class, CrmLeadSourcePolicy::class);
        Gate::policy(CrmPipelineStage::class, CrmPipelineStagePolicy::class);
        Gate::policy(CrmAssignmentRule::class, CrmAssignmentRulePolicy::class);

        foreach ([
            User::class,
            Role::class,
            Permission::class,
            Setting::class,
            CompanyProfile::class,
            ThemePreset::class,
            UserPreference::class,
            ProductCategory::class,
            Unit::class,
            Tax::class,
            Product::class,
            Customer::class,
            Supplier::class,
            Warehouse::class,
            StockMovement::class,
            SalesOrder::class,
            SalesQuotation::class,
            SalesDelivery::class,
            SalesInvoice::class,
            SalesInvoiceItem::class,
            SalesReceipt::class,
            PurchaseOrder::class,
            PurchaseReceipt::class,
            SupplierBill::class,
            SupplierBillItem::class,
            SupplierPayment::class,
            ChartOfAccount::class,
            JournalEntry::class,
            JournalLine::class,
            CrmAccount::class,
            CrmContact::class,
            CrmLeadSource::class,
            CrmPipelineStage::class,
            CrmLead::class,
            CrmOpportunity::class,
            CrmActivity::class,
            CrmTask::class,
            CrmNote::class,
            CrmAttachment::class,
            CrmEmail::class,
            CrmCall::class,
            CrmTag::class,
            CrmAssignmentRule::class,
            CrmStageHistory::class,
            CrmActivityLog::class,
        ] as $modelClass) {
            $modelClass::observe(AuditableObserver::class);
        }

        Event::listen(Login::class, function (Login $event): void {
            if ($event->user instanceof User) {
                $event->user->forceFill([
                    'last_login_at' => now(),
                ])->saveQuietly();
            }
        });
    }
}
