<?php

namespace App\Filament\Resources\SolicitudesInvestiga;

use App\Filament\Resources\SolicitudesInvestiga\Pages\CrearSolicitudInvestiga;
use App\Filament\Resources\SolicitudesInvestiga\Pages\GestionarProyectoInvestiga;
use App\Filament\Resources\SolicitudesInvestiga\Pages\ListarSolicitudesInvestiga;
use App\Filament\Resources\SolicitudesInvestiga\Pages\VerSolicitudInvestiga;
use App\Filament\Resources\SolicitudesInvestiga\Schemas\FormularioSolicitudInvestiga;
use App\Filament\Resources\SolicitudesInvestiga\Tables\TablaSolicitudesInvestiga;
use App\Models\SolicitudInvestiga;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SolicitudInvestigaResource extends Resource
{
    protected static ?string $model = SolicitudInvestiga::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLightBulb;

    protected static ?string $navigationLabel = 'Ideas y solicitudes';

    protected static ?string $modelLabel = 'solicitud';

    protected static ?string $pluralModelLabel = 'Ideas y solicitudes';

    protected static ?string $slug = 'investiga-lab/solicitudes';

    protected static string|\UnitEnum|null $navigationGroup = 'INVESTIGALAB';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'codigo';

    public static function form(Schema $schema): Schema
    {
        return FormularioSolicitudInvestiga::configurar($schema);
    }

    public static function table(Table $table): Table
    {
        return TablaSolicitudesInvestiga::configurar($table);
    }

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, ['gerencia', 'investiga_lab'], true);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'investiga_lab';
    }

    public static function canEdit(Model $record): bool
    {
        return auth()->user()?->role === 'investiga_lab';
    }

    public static function getPages(): array
    {
        return [
            'index' => ListarSolicitudesInvestiga::route('/'),
            'create' => CrearSolicitudInvestiga::route('/create'),
            'view' => VerSolicitudInvestiga::route('/{record}'),
            'edit' => GestionarProyectoInvestiga::route('/{record}/gestionar'),
        ];
    }
}
