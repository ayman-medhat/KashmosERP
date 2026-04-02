<?php

namespace App\Modules\Sales\Filament\Resources;

use App\Modules\Sales\Filament\Resources\SalesInvoiceResource\Pages;
use App\Modules\Sales\Models\SalesDelivery;
use App\Modules\Sales\Models\SalesInvoice;
use App\Modules\Sales\Services\SalesInvoiceService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesInvoiceResource extends Resource
{
    protected static ?string $model = SalesInvoice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-currency-dollar';

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('invoice_no')->label('Invoice No')->placeholder('Auto')->maxLength(100),
                Select::make('sales_delivery_id')
                    ->label('Sales Delivery')
                    ->options(
                        SalesDelivery::query()
                            ->with(['order.customer'])
                            ->where('status', 'confirmed')
                            ->whereDoesntHave('invoice')
                            ->orderByDesc('id')
                            ->get()
                            ->mapWithKeys(fn (SalesDelivery $delivery): array => [
                                $delivery->id => $delivery->delivery_no.' - '.($delivery->order?->customer?->name ?? 'Customer'),
                            ])
                            ->all()
                    )
                    ->searchable()
                    ->preload()
                    ->required(),
                DatePicker::make('invoice_date')->required()->default(now()),
                DatePicker::make('due_date')->default(now()),
                Textarea::make('notes_translations.en')->label('Notes (English)'),
                Textarea::make('notes_translations.ar')->label('Notes (Arabic)'),
            ])
            ->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('invoice_no')->searchable()->sortable(),
                TextColumn::make('invoice_date')->date()->sortable(),
                TextColumn::make('customer.name')->label('Customer')->searchable(),
                TextColumn::make('delivery.delivery_no')->label('Delivery')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('grand_total')->money('EGP'),
                TextColumn::make('paid_total')->money('EGP'),
                TextColumn::make('outstanding_amount')
                    ->label('Outstanding')
                    ->money('EGP'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'posted' => 'Posted',
                        'partially_paid' => 'Partially Paid',
                        'paid' => 'Paid',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->recordActions([
                Action::make('post')
                    ->color('success')
                    ->icon('heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->authorize('post')
                    ->visible(fn (SalesInvoice $record): bool => $record->status === 'draft')
                    ->action(fn (SalesInvoice $record) => app(SalesInvoiceService::class)->post($record)),
                \Filament\Actions\ViewAction::make(),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesInvoices::route('/'),
            'create' => Pages\CreateSalesInvoice::route('/create'),
            'view' => Pages\ViewSalesInvoice::route('/{record}'),
        ];
    }
}

