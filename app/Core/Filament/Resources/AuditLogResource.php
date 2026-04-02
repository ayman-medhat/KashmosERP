<?php

namespace App\Core\Filament\Resources;

use App\Core\Filament\Resources\AuditLogResource\Pages;
use App\Core\Models\AuditLog;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 1;

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event')->badge()->sortable(),
                TextColumn::make('auditable_type')->label('Model')->searchable()->toggleable(),
                TextColumn::make('auditable_id')->label('Record')->sortable(),
                TextColumn::make('user.email')->label('User')->searchable(),
                TextColumn::make('ip_address')->searchable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->recordActions([
                \Filament\Actions\ViewAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
