<?php

namespace App\Filament\Resources\ProveedoresDami3d;

use App\Filament\Resources\ProveedoresDami3d\Pages\CrearProveedorDami3d;
use App\Filament\Resources\ProveedoresDami3d\Pages\EditarProveedorDami3d;
use App\Filament\Resources\ProveedoresDami3d\Pages\ListarProveedoresDami3d;
use App\Filament\Resources\ProveedoresDami3d\Pages\VerProveedorDami3d;
use App\Filament\Resources\ProveedoresDami3d\Schemas\FormularioProveedorDami3d;
use App\Filament\Resources\ProveedoresDami3d\Tables\TablaProveedoresDami3d;
use App\Models\ProveedorDami3d;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProveedorDami3dResource extends Resource
{
    protected static ?string $model = ProveedorDami3d::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;
    protected static ?string $navigationLabel = 'Proveedores';
    protected static ?string $modelLabel = 'proveedor';
    protected static ?string $pluralModelLabel = 'Proveedores de DAMI 3D';
    protected static string|\UnitEnum|null $navigationGroup = 'DAMI 3D';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'dami-3d/proveedores';
    protected static ?string $recordTitleAttribute = 'razon_social';

    public static function form(Schema $schema): Schema { return FormularioProveedorDami3d::configurar($schema); }
    public static function table(Table $table): Table { return TablaProveedoresDami3d::configurar($table); }
    public static function canViewAny(): bool { return in_array(auth()->user()?->role, ['gerencia','dami_3d'], true); }
    public static function canCreate(): bool { return in_array(auth()->user()?->role, ['gerencia','dami_3d'], true); }
    public static function canEdit(Model $record): bool { return in_array(auth()->user()?->role, ['gerencia','dami_3d'], true); }
    public static function canDelete(Model $record): bool { return false; }
    public static function canDeleteAny(): bool { return false; }

    public static function getPages(): array
    {
        return [
            'index'=>ListarProveedoresDami3d::route('/'), 'create'=>CrearProveedorDami3d::route('/crear'),
            'view'=>VerProveedorDami3d::route('/{record}'), 'edit'=>EditarProveedorDami3d::route('/{record}/editar'),
        ];
    }
}
