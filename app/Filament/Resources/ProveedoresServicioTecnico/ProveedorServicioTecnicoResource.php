<?php

namespace App\Filament\Resources\ProveedoresServicioTecnico;

use App\Filament\Resources\ProveedoresServicioTecnico\Pages\CrearProveedorServicioTecnico;
use App\Filament\Resources\ProveedoresServicioTecnico\Pages\EditarProveedorServicioTecnico;
use App\Filament\Resources\ProveedoresServicioTecnico\Pages\ListarProveedoresServicioTecnico;
use App\Filament\Resources\ProveedoresServicioTecnico\Pages\VerProveedorServicioTecnico;
use App\Filament\Resources\ProveedoresServicioTecnico\Schemas\FormularioProveedorServicioTecnico;
use App\Filament\Resources\ProveedoresServicioTecnico\Tables\TablaProveedoresServicioTecnico;
use App\Models\ProveedorServicioTecnico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ProveedorServicioTecnicoResource extends Resource
{
    protected static ?string $model = ProveedorServicioTecnico::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;
    protected static ?string $navigationLabel = 'Proveedores';
    protected static ?string $modelLabel = 'proveedor técnico';
    protected static ?string $pluralModelLabel = 'Proveedores de Servicio Técnico';
    protected static string|\UnitEnum|null $navigationGroup = 'SERVICIO TÉCNICO';
    protected static ?int $navigationSort = 2;
    protected static ?string $slug = 'servicio-tecnico/proveedores';
    protected static ?string $recordTitleAttribute = 'razon_social';

    public static function form(Schema $schema): Schema
    {
        return FormularioProveedorServicioTecnico::configurar($schema);
    }

    public static function table(Table $table): Table
    {
        return TablaProveedoresServicioTecnico::configurar($table);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, ['gerencia', 'servicio_tecnico'], true);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'servicio_tecnico';
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === 'servicio_tecnico';
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListarProveedoresServicioTecnico::route('/'),
            'create' => CrearProveedorServicioTecnico::route('/crear'),
            'view' => VerProveedorServicioTecnico::route('/{record}'),
            'edit' => EditarProveedorServicioTecnico::route('/{record}/editar'),
        ];
    }
}
