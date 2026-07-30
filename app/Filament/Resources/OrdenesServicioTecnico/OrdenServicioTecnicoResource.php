<?php

namespace App\Filament\Resources\OrdenesServicioTecnico;

use App\Filament\Resources\OrdenesServicioTecnico\Pages\CrearOrdenServicioTecnico;
use App\Filament\Resources\OrdenesServicioTecnico\Pages\GestionarOrdenServicioTecnico;
use App\Filament\Resources\OrdenesServicioTecnico\Pages\ListarOrdenesServicioTecnico;
use App\Filament\Resources\OrdenesServicioTecnico\Pages\VerOrdenServicioTecnico;
use App\Filament\Resources\OrdenesServicioTecnico\Schemas\FormularioIngresoServicioTecnico;
use App\Filament\Resources\OrdenesServicioTecnico\Tables\TablaOrdenesServicioTecnico;
use App\Models\OrdenServicioTecnico;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class OrdenServicioTecnicoResource extends Resource
{
    protected static ?string $model = OrdenServicioTecnico::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;
    protected static ?string $navigationLabel = 'Órdenes de servicio';
    protected static ?string $modelLabel = 'orden de servicio';
    protected static ?string $pluralModelLabel = 'Órdenes de servicio';
    protected static ?string $slug = 'servicio-tecnico/ordenes';
    protected static string|\UnitEnum|null $navigationGroup = 'SERVICIO TÉCNICO';
    protected static ?int $navigationSort = 1;
    protected static ?string $recordTitleAttribute = 'codigo';

    public static function form(Schema $schema): Schema
    {
        return FormularioIngresoServicioTecnico::configurar($schema);
    }

    public static function table(Table $table): Table
    {
        return TablaOrdenesServicioTecnico::configurar($table);
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

    public static function getPages(): array
    {
        return [
            'index' => ListarOrdenesServicioTecnico::route('/'),
            'create' => CrearOrdenServicioTecnico::route('/create'),
            'view' => VerOrdenServicioTecnico::route('/{record}'),
            'edit' => GestionarOrdenServicioTecnico::route('/{record}/gestionar'),
        ];
    }
}
