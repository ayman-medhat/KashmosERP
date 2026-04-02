<?php

namespace App\Core\Filament\Pages;

use App\Core\Enums\Locale;
use App\Core\Enums\ThemeMode;
use App\Core\Models\ThemePreset;
use App\Core\Services\SettingsService;
use App\Modules\Accounting\Models\ChartOfAccount;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SettingsPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.settings-page';

    public ?array $data = [];

    public function mount(SettingsService $settings): void
    {
        $this->form->fill([
            'default_locale' => $settings->get('localization', 'default_locale', 'en'),
            'allow_negative_stock' => $settings->get('inventory', 'allow_negative_stock', false),
            'inventory_account_code' => $settings->get('accounting', 'inventory_account_code', '1200'),
            'accounts_payable_account_code' => $settings->get('accounting', 'accounts_payable_account_code', '2000'),
            'cogs_account_code' => $settings->get('accounting', 'cogs_account_code', '5000'),
            'accounts_receivable_account_code' => $settings->get('accounting', 'accounts_receivable_account_code', '1100'),
            'sales_revenue_account_code' => $settings->get('accounting', 'sales_revenue_account_code', '4000'),
            'cash_account_code' => $settings->get('accounting', 'cash_account_code', '1000'),
            'theme_key' => auth()->user()?->preference?->theme_key ?? 'amber',
            'theme_mode' => auth()->user()?->preference?->theme_mode ?? 'system',
            'user_locale' => auth()->user()?->preference?->locale ?? auth()->user()?->locale ?? 'en',
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->check();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Personal Preferences')
                    ->schema([
                        Select::make('user_locale')
                            ->label('Language')
                            ->options(collect(Locale::cases())->mapWithKeys(fn (Locale $locale) => [$locale->value => $locale->label()])),
                        Select::make('theme_mode')
                            ->label('Theme Mode')
                            ->options(collect(ThemeMode::cases())->mapWithKeys(fn (ThemeMode $mode) => [$mode->value => $mode->label()])),
                        Select::make('theme_key')
                            ->label('Color Theme')
                            ->options(ThemePreset::query()->pluck('name', 'key'))
                            ->searchable(),
                    ])
                    ->columns(3),
                Section::make('System Settings')
                    ->schema([
                        Select::make('default_locale')
                            ->label('Default Locale')
                            ->options(collect(Locale::cases())->mapWithKeys(fn (Locale $locale) => [$locale->value => $locale->label()])),
                        Toggle::make('allow_negative_stock')
                            ->label('Allow Negative Stock'),
                        Select::make('inventory_account_code')
                            ->label('Inventory Account')
                            ->options($this->accountCodeOptions())
                            ->searchable(),
                        Select::make('accounts_payable_account_code')
                            ->label('Accounts Payable Account')
                            ->options($this->accountCodeOptions())
                            ->searchable(),
                        Select::make('cogs_account_code')
                            ->label('COGS Account')
                            ->options($this->accountCodeOptions())
                            ->searchable(),
                        Select::make('accounts_receivable_account_code')
                            ->label('Accounts Receivable Account')
                            ->options($this->accountCodeOptions())
                            ->searchable(),
                        Select::make('sales_revenue_account_code')
                            ->label('Sales Revenue Account')
                            ->options($this->accountCodeOptions())
                            ->searchable(),
                        Select::make('cash_account_code')
                            ->label('Cash Account')
                            ->options($this->accountCodeOptions())
                            ->searchable(),
                    ])
                    ->columns(2)
                    ->visible(fn (): bool => auth()->user()?->can('core.settings.manage') ?? false),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->submit('save'),
        ];
    }

    public function save(SettingsService $settings): void
    {
        $state = $this->form->getState();

        auth()->user()?->preference()->updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'locale' => $state['user_locale'],
                'theme_mode' => $state['theme_mode'],
                'theme_key' => $state['theme_key'],
            ],
        );

        auth()->user()?->forceFill([
            'locale' => $state['user_locale'],
        ])->saveQuietly();

        if (auth()->user()?->can('core.settings.manage')) {
            $settings->put('localization', 'default_locale', $state['default_locale'], true);
            $settings->put('inventory', 'allow_negative_stock', $state['allow_negative_stock'], false);
            $settings->put('accounting', 'inventory_account_code', $state['inventory_account_code'], false);
            $settings->put('accounting', 'accounts_payable_account_code', $state['accounts_payable_account_code'], false);
            $settings->put('accounting', 'cogs_account_code', $state['cogs_account_code'], false);
            $settings->put('accounting', 'accounts_receivable_account_code', $state['accounts_receivable_account_code'], false);
            $settings->put('accounting', 'sales_revenue_account_code', $state['sales_revenue_account_code'], false);
            $settings->put('accounting', 'cash_account_code', $state['cash_account_code'], false);
        }

        Notification::make()
            ->title('Settings saved successfully.')
            ->success()
            ->send();
    }

    /**
     * @return array<string, string>
     */
    protected function accountCodeOptions(): array
    {
        return ChartOfAccount::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get()
            ->mapWithKeys(fn (ChartOfAccount $account): array => [$account->code => $account->code.' - '.$account->name])
            ->all();
    }
}
