<?php

namespace App\Filament\Resources\MarcasProveedorServicioTecnico;

use App\Filament\Resources\MarcasProveedorServicioTecnico\Pages\CrearMarcaProveedorServicioTecnico;
use App\Filament\Resources\MarcasProveedorServicioTecnico\Pages\EditarMarcaProveedorServicioTecnico;
use App\Filament\Resources\MarcasProveedorServicioTecnico\Pages\ListarMarcasProveedorServicioTecnico;
use App\Models\MarcaProveedorServicioTecnico;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class MarcaProveedorServicioTecnicoResource extends Resource
{
    protected static ?string $model = MarcaProveedorServicioTecnico::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookmark;
    protected static ?string $navigationLabel = 'Marcas de proveedores';
    protected static ?string $modelLabel = 'marca';
    protected static ?string $pluralModelLabel = 'Marcas de proveedores técnicos';
    protected static string|\UnitEnum|null $navigationGroup = 'SERVICIO TÉCNICO';
    protected static ?int $navigationSort = 4;
    protected static ?string $slug = 'servicio-tecnico/proveedores/marcas';

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

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Marca')
                ->description('Fabricante o marca comercial de equipos y repuestos.')
                ->schema([
                    TextInput::make('nombre')->required()->unique(ignoreRecord: true)->maxLength(120),
                    Toggle::make('activo')->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'damian-supplier-table damian-technical-catalog-table'])
            ->columns([
                TextColumn::make('nombre')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('proveedores_count')->label('Proveedores')->counts('proveedores')->badge()->color('info'),
                TextColumn::make('activo')->badge()->formatStateUsing(fn ($state) => $state ? 'Activa' : 'Inactiva')->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->defaultSort('nombre');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListarMarcasProveedorServicioTecnico::route('/'),
            'create' => CrearMarcaProveedorServicioTecnico::route('/crear'),
            'edit' => EditarMarcaProveedorServicioTecnico::route('/{record}/editar'),
        ];
    }
}
