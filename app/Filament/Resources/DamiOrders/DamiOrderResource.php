<?php

namespace App\Filament\Resources\DamiOrders;

use App\Filament\Resources\DamiOrders\Pages\CreateDamiOrder;
use App\Filament\Resources\DamiOrders\Pages\EditDamiOrder;
use App\Filament\Resources\DamiOrders\Pages\ListDamiOrders;
use App\Filament\Resources\DamiOrders\Schemas\DamiOrderForm;
use App\Filament\Resources\DamiOrders\Tables\DamiOrdersTable;
use App\Models\DamiOrder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class DamiOrderResource extends Resource
{
    protected static ?string $model = DamiOrder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Pedidos';

    protected static ?string $modelLabel = 'pedido';

    protected static ?string $pluralModelLabel = 'Pedidos';

    protected static string|\UnitEnum|null $navigationGroup = 'DAMI 3D';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function form(Schema $schema): Schema
    {
        return DamiOrderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DamiOrdersTable::configure($table);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, ['gerencia', 'dami_3d'], true);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'dami_3d';
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === 'dami_3d';
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->role === 'dami_3d';
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->role === 'dami_3d';
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDamiOrders::route('/'),
            'create' => CreateDamiOrder::route('/create'),
            'edit' => EditDamiOrder::route('/{record}/edit'),
        ];
    }
}
