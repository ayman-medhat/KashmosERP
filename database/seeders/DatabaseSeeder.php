<?php

namespace Database\Seeders;

use App\Core\Models\CompanyProfile;
use App\Core\Models\Role;
use App\Core\Models\Setting;
use App\Core\Models\ThemePreset;
use App\Core\Models\User;
use App\Core\Support\CorePermissions;
use App\Core\Support\KashmosTheme;
use App\Modules\Accounting\Models\ChartOfAccount;
use App\Modules\Inventory\Services\StockMovementService;
use App\Modules\MasterData\Models\Customer;
use App\Modules\MasterData\Models\Product;
use App\Modules\MasterData\Models\ProductCategory;
use App\Modules\MasterData\Models\Supplier;
use App\Modules\MasterData\Models\Tax;
use App\Modules\MasterData\Models\Unit;
use App\Modules\MasterData\Models\Warehouse;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (CorePermissions::definitions() as $attributes) {
            \App\Core\Models\Permission::query()->updateOrCreate(
                ['name' => $attributes['name'], 'guard_name' => 'web'],
                $attributes,
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $role = Role::query()->updateOrCreate(
            ['name' => 'super-admin', 'guard_name' => 'web'],
            [
                'display_name' => 'Super Admin',
                'is_system' => true,
            ],
        );

        $role->syncPermissions(\App\Core\Models\Permission::query()->pluck('name')->all());

        $user = User::query()->updateOrCreate([
            'email' => 'kashmos@outlook.com',
        ], [
            'uuid' => (string) str()->uuid(),
            'name' => 'Kashmos Admin',
            'phone' => null,
            'locale' => 'en',
            'is_active' => true,
            'password' => Hash::make('password'),
        ]);

        $user->syncRoles([$role]);

        CompanyProfile::query()->updateOrCreate(
            ['id' => 1],
            [
                'name_translations' => [
                    'en' => 'Kashmos ERP',
                    'ar' => 'كاشموس',
                ],
                'email' => 'kashmos@outlook.com',
                'phone' => null,
                'address_translations' => [
                    'en' => 'Head Office',
                    'ar' => 'المكتب الرئيسي',
                ],
                'timezone' => 'Africa/Cairo',
                'currency_code' => 'EGP',
            ],
        );

        foreach ([
            ['key' => 'amber', 'name' => 'Amber', 'palette' => KashmosTheme::palette('amber')],
            ['key' => 'emerald', 'name' => 'Emerald', 'palette' => KashmosTheme::palette('emerald')],
            ['key' => 'blue', 'name' => 'Blue', 'palette' => KashmosTheme::palette('blue')],
        ] as $preset) {
            ThemePreset::query()->updateOrCreate(
                ['key' => $preset['key']],
                [
                    'name' => $preset['name'],
                    'mode' => 'system',
                    'palette' => $preset['palette'],
                    'is_default' => $preset['key'] === 'amber',
                ],
            );
        }

        foreach ([
            ['group' => 'branding', 'key' => 'app_name', 'value' => 'Kashmos ERP'],
            ['group' => 'branding', 'key' => 'support_email', 'value' => 'kashmos@outlook.com'],
            ['group' => 'localization', 'key' => 'default_locale', 'value' => 'en'],
            ['group' => 'localization', 'key' => 'available_locales', 'value' => ['en', 'ar']],
            ['group' => 'inventory', 'key' => 'allow_negative_stock', 'value' => false],
            ['group' => 'accounting', 'key' => 'inventory_account_code', 'value' => '1200'],
            ['group' => 'accounting', 'key' => 'accounts_payable_account_code', 'value' => '2000'],
            ['group' => 'accounting', 'key' => 'cogs_account_code', 'value' => '5000'],
            ['group' => 'accounting', 'key' => 'accounts_receivable_account_code', 'value' => '1100'],
            ['group' => 'accounting', 'key' => 'sales_revenue_account_code', 'value' => '4000'],
            ['group' => 'accounting', 'key' => 'cash_account_code', 'value' => '1000'],
            ['group' => 'crm', 'key' => 'reminders_enabled', 'value' => true],
            ['group' => 'crm', 'key' => 'reminder_look_ahead_hours', 'value' => 24],
        ] as $setting) {
            Setting::query()->updateOrCreate(
                ['group' => $setting['group'], 'key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => get_debug_type($setting['value']),
                    'is_public' => in_array($setting['key'], ['app_name', 'support_email', 'default_locale', 'available_locales'], true),
                ],
            );
        }

        $unit = Unit::query()->updateOrCreate(
            ['code' => 'PCS'],
            [
                'uuid' => (string) str()->uuid(),
                'name_translations' => ['en' => 'Pieces', 'ar' => 'قطعة'],
                'precision' => 2,
                'is_active' => true,
            ],
        );

        $category = ProductCategory::query()->updateOrCreate(
            ['uuid' => '11111111-1111-1111-1111-111111111111'],
            [
                'name_translations' => ['en' => 'General', 'ar' => 'عام'],
                'description_translations' => ['en' => 'Default category', 'ar' => 'فئة افتراضية'],
                'is_active' => true,
            ],
        );

        $tax = Tax::query()->updateOrCreate(
            ['code' => 'VAT14'],
            [
                'uuid' => (string) str()->uuid(),
                'name_translations' => ['en' => 'VAT 14%', 'ar' => 'ضريبة القيمة 14%'],
                'rate' => 14.0000,
                'is_inclusive' => false,
                'is_active' => true,
            ],
        );

        Warehouse::query()->updateOrCreate(
            ['code' => 'MAIN'],
            [
                'uuid' => (string) str()->uuid(),
                'name_translations' => ['en' => 'Main Warehouse', 'ar' => 'المخزن الرئيسي'],
                'address_translations' => ['en' => 'Head office', 'ar' => 'المقر الرئيسي'],
                'is_default' => true,
                'is_active' => true,
            ],
        );

        Customer::query()->updateOrCreate(
            ['code' => 'CUST-001'],
            [
                'uuid' => (string) str()->uuid(),
                'name_translations' => ['en' => 'Walk-in Customer', 'ar' => 'عميل نقدي'],
                'email' => null,
                'phone' => null,
                'address_translations' => null,
                'credit_limit' => 0,
                'is_active' => true,
            ],
        );

        Supplier::query()->updateOrCreate(
            ['code' => 'SUP-001'],
            [
                'uuid' => (string) str()->uuid(),
                'name_translations' => ['en' => 'Default Supplier', 'ar' => 'مورد افتراضي'],
                'email' => null,
                'phone' => null,
                'address_translations' => null,
                'is_active' => true,
            ],
        );

        Product::query()->updateOrCreate(
            ['sku' => 'PROD-001'],
            [
                'uuid' => (string) str()->uuid(),
                'name_translations' => ['en' => 'Sample Product', 'ar' => 'منتج تجريبي'],
                'description_translations' => ['en' => 'Bootstrap product', 'ar' => 'منتج تأسيسي'],
                'product_category_id' => $category->id,
                'unit_id' => $unit->id,
                'tax_id' => $tax->id,
                'cost_price' => 10.0000,
                'sale_price' => 15.0000,
                'opening_stock' => 100.000000,
                'reorder_level' => 10.000000,
                'track_stock' => true,
                'is_active' => true,
            ],
        );

        foreach ([
            [
                'code' => '1000',
                'name_translations' => ['en' => 'Cash', 'ar' => 'النقدية'],
                'account_type' => 'asset',
                'normal_balance' => 'debit',
            ],
            [
                'code' => '1100',
                'name_translations' => ['en' => 'Accounts Receivable', 'ar' => 'الذمم المدينة'],
                'account_type' => 'asset',
                'normal_balance' => 'debit',
            ],
            [
                'code' => '1200',
                'name_translations' => ['en' => 'Inventory', 'ar' => 'المخزون'],
                'account_type' => 'asset',
                'normal_balance' => 'debit',
            ],
            [
                'code' => '2000',
                'name_translations' => ['en' => 'Accounts Payable', 'ar' => 'الذمم الدائنة'],
                'account_type' => 'liability',
                'normal_balance' => 'credit',
            ],
            [
                'code' => '3000',
                'name_translations' => ['en' => 'Owner Equity', 'ar' => 'حقوق الملكية'],
                'account_type' => 'equity',
                'normal_balance' => 'credit',
            ],
            [
                'code' => '4000',
                'name_translations' => ['en' => 'Sales Revenue', 'ar' => 'إيرادات المبيعات'],
                'account_type' => 'revenue',
                'normal_balance' => 'credit',
            ],
            [
                'code' => '5000',
                'name_translations' => ['en' => 'Cost of Goods Sold', 'ar' => 'تكلفة البضاعة المباعة'],
                'account_type' => 'expense',
                'normal_balance' => 'debit',
            ],
        ] as $account) {
            ChartOfAccount::query()->updateOrCreate(
                ['code' => $account['code']],
                [
                    'uuid' => (string) str()->uuid(),
                    'name_translations' => $account['name_translations'],
                    'account_type' => $account['account_type'],
                    'normal_balance' => $account['normal_balance'],
                    'is_active' => true,
                    'is_system' => true,
                    'created_by' => $user->id,
                ],
            );
        }

        $seedProduct = Product::query()->where('sku', 'PROD-001')->first();
        $defaultWarehouse = Warehouse::query()->where('code', 'MAIN')->first();

        if ($seedProduct && $defaultWarehouse) {
            $movementService = app(StockMovementService::class);
            $existingBalance = $movementService->currentStock($seedProduct->id, $defaultWarehouse->id);

            if ($existingBalance == 0.0) {
                $movementService->openingBalance(
                    product: $seedProduct,
                    warehouse: $defaultWarehouse,
                    quantity: 100.0,
                    unitCost: 10.0,
                );
            }
        }

        $this->call(CrmSeeder::class);
    }
}
