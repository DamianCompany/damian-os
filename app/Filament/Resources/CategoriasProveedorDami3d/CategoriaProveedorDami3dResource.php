<?php
namespace App\Filament\Resources\CategoriasProveedorDami3d;
use App\Filament\Resources\CategoriasProveedorDami3d\Pages\CrearCategoriaProveedorDami3d;
use App\Filament\Resources\CategoriasProveedorDami3d\Pages\EditarCategoriaProveedorDami3d;
use App\Filament\Resources\CategoriasProveedorDami3d\Pages\ListarCategoriasProveedorDami3d;
use App\Models\CategoriaProveedorDami3d;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class CategoriaProveedorDami3dResource extends Resource {
 protected static ?string $model=CategoriaProveedorDami3d::class; protected static string|BackedEnum|null $navigationIcon=Heroicon::OutlinedTag; protected static ?string $navigationLabel='Categorías de proveedores'; protected static string|\UnitEnum|null $navigationGroup='DAMI 3D'; protected static ?int $navigationSort=4; protected static ?string $slug='dami-3d/proveedores/categorias';
 public static function canViewAny():bool{return auth()->user()?->role==='gerencia';} public static function canCreate():bool{return auth()->user()?->role==='gerencia';} public static function canEdit(\Illuminate\Database\Eloquent\Model $record):bool{return auth()->user()?->role==='gerencia';} public static function canDelete(\Illuminate\Database\Eloquent\Model $record):bool{return false;}
 public static function form(Schema $schema):Schema{return $schema->components([Section::make('Categoría de material')->schema([TextInput::make('nombre')->required()->unique(ignoreRecord:true),TextInput::make('orden')->numeric()->minValue(0)->default(0),Toggle::make('activo')->default(true)])->columns(3)]);}
 public static function table(Table $table):Table{return $table->columns([TextColumn::make('nombre')->searchable()->sortable(),TextColumn::make('orden')->sortable(),TextColumn::make('activo')->badge()->formatStateUsing(fn($s)=>$s?'Activa':'Inactiva')->color(fn($s)=>$s?'success':'gray')])->defaultSort('orden');}
 public static function getPages():array{return ['index'=>ListarCategoriasProveedorDami3d::route('/'),'create'=>CrearCategoriaProveedorDami3d::route('/crear'),'edit'=>EditarCategoriaProveedorDami3d::route('/{record}/editar')];}
}
