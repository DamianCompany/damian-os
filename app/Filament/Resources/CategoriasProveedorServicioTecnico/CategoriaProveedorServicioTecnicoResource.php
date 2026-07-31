<?php

namespace App\Filament\Resources\CategoriasProveedorServicioTecnico;

use App\Filament\Resources\CategoriasProveedorServicioTecnico\Pages\CrearCategoriaProveedorServicioTecnico;
use App\Filament\Resources\CategoriasProveedorServicioTecnico\Pages\EditarCategoriaProveedorServicioTecnico;
use App\Filament\Resources\CategoriasProveedorServicioTecnico\Pages\ListarCategoriasProveedorServicioTecnico;
use App\Models\CategoriaProveedorServicioTecnico;
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

class CategoriaProveedorServicioTecnicoResource extends Resource
{
    protected static ?string $model = CategoriaProveedorServicioTecnico::class;
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;
    protected static ?string $navigationLabel = 'Categorías de proveedores';
    protected static ?string $modelLabel = 'categoría';
    protected static ?string $pluralModelLabel = 'Categorías de proveedores técnicos';
    protected static string|\UnitEnum|null $navigationGroup = 'SERVICIO TÉCNICO';
    protected static ?int $navigationSort = 3;
    protected static ?string $slug = 'servicio-tecnico/proveedores/categorias';

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
            Section::make('Categoría')
                ->description('Agrupa los productos que ofrecen los proveedores.')
                ->schema([
                    TextInput::make('nombre')->required()->unique(ignoreRecord: true)->maxLength(120),
                    TextInput::make('orden')->numeric()->minValue(0)->default(0),
                    Toggle::make('activo')->default(true),
                ])
                ->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->extraAttributes(['class' => 'damian-supplier-table damian-technical-catalog-table'])
            ->columns([
                TextColumn::make('nombre')->searchable()->sortable()->weight('semibold'),
                TextColumn::make('proveedores_count')->label('Proveedores')->counts('proveedores')->badge()->color('info'),
                TextColumn::make('orden')->sortable(),
                TextColumn::make('activo')->badge()->formatStateUsing(fn ($state) => $state ? 'Activa' : 'Inactiva')->color(fn ($state) => $state ? 'success' : 'gray'),
            ])
            ->defaultSort('orden');
    }

    public static function getPages(): array
    {
        return [
            'index' => ListarCategoriasProveedorServicioTecnico::route('/'),
            'create' => CrearCategoriaProveedorServicioTecnico::route('/crear'),
            'edit' => EditarCategoriaProveedorServicioTecnico::route('/{record}/editar'),
        ];
    }
}
