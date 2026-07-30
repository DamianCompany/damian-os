<?php

namespace App\Filament\Resources\SolicitudesAutomation;

use App\Filament\Resources\SolicitudesAutomation\Pages\CrearSolicitudAutomation;
use App\Filament\Resources\SolicitudesAutomation\Pages\GestionarProyectoAutomation;
use App\Filament\Resources\SolicitudesAutomation\Pages\ListarSolicitudesAutomation;
use App\Filament\Resources\SolicitudesAutomation\Pages\VerSolicitudAutomation;
use App\Filament\Resources\SolicitudesAutomation\Schemas\FormularioSolicitudAutomation;
use App\Filament\Resources\SolicitudesAutomation\Tables\TablaSolicitudesAutomation;
use App\Models\SolicitudAutomation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SolicitudAutomationResource extends Resource
{
    protected static ?string $model = SolicitudAutomation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?string $navigationLabel = 'Solicitudes y proyectos';

    protected static ?string $modelLabel = 'solicitud de Automation';

    protected static ?string $pluralModelLabel = 'Solicitudes y proyectos';

    protected static ?string $slug = 'automation/solicitudes';

    protected static string|\UnitEnum|null $navigationGroup = 'DAMIAN AUTOMATION';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'codigo';

    public static function form(Schema $schema): Schema
    {
        return FormularioSolicitudAutomation::configurar($schema);
    }

    public static function table(Table $table): Table
    {
        return TablaSolicitudesAutomation::configurar($table);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, ['gerencia', 'automation'], true);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'automation';
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === 'automation';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListarSolicitudesAutomation::route('/'),
            'create' => CrearSolicitudAutomation::route('/create'),
            'view' => VerSolicitudAutomation::route('/{record}'),
            'edit' => GestionarProyectoAutomation::route('/{record}/gestionar'),
        ];
    }
}
