<?php

namespace App\Core\Filament\Pages;

use App\Core\Models\CompanyProfile;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CompanyProfilePage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Company Profile';

    protected static string|\UnitEnum|null $navigationGroup = 'System';

    protected static ?int $navigationSort = 11;

    protected string $view = 'filament.pages.company-profile-page';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::canAccess(), 403);

        $profile = CompanyProfile::query()->first();

        $this->form->fill([
            'name_en' => $profile?->name_translations['en'] ?? 'Kashmos ERP',
            'name_ar' => $profile?->name_translations['ar'] ?? 'كاشموس',
            'email' => $profile?->email ?? 'kashmos@outlook.com',
            'phone' => $profile?->phone,
            'address_en' => $profile?->address_translations['en'] ?? null,
            'address_ar' => $profile?->address_translations['ar'] ?? null,
            'currency_code' => $profile?->currency_code ?? 'EGP',
            'timezone' => $profile?->timezone ?? 'Africa/Cairo',
            'tax_number' => $profile?->tax_number,
        ]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('core.company-profile.manage') ?? false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Company Details')
                    ->schema([
                        TextInput::make('name_en')->label('Name (English)')->required()->maxLength(255),
                        TextInput::make('name_ar')->label('Name (Arabic)')->required()->maxLength(255),
                        TextInput::make('email')->email()->maxLength(255),
                        TextInput::make('phone')->tel()->maxLength(255),
                        TextInput::make('currency_code')->required()->length(3),
                        TextInput::make('timezone')->required()->maxLength(100),
                        TextInput::make('tax_number')->maxLength(255),
                        TextInput::make('address_en')->label('Address (English)')->maxLength(255),
                        TextInput::make('address_ar')->label('Address (Arabic)')->maxLength(255),
                    ])
                    ->columns(2),
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

    public function save(): void
    {
        $state = $this->form->getState();

        CompanyProfile::query()->updateOrCreate(
            ['id' => 1],
            [
                'name_translations' => [
                    'en' => $state['name_en'],
                    'ar' => $state['name_ar'],
                ],
                'email' => $state['email'],
                'phone' => $state['phone'],
                'address_translations' => [
                    'en' => $state['address_en'],
                    'ar' => $state['address_ar'],
                ],
                'currency_code' => $state['currency_code'],
                'timezone' => $state['timezone'],
                'tax_number' => $state['tax_number'],
            ],
        );

        Notification::make()
            ->title('Company profile updated.')
            ->success()
            ->send();
    }
}
