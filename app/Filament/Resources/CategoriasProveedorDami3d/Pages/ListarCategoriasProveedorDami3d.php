<?php
namespace App\Filament\Resources\CategoriasProveedorDami3d\Pages;
use App\Filament\Resources\CategoriasProveedorDami3d\CategoriaProveedorDami3dResource; use Filament\Actions\CreateAction; use Filament\Resources\Pages\ListRecords;
class ListarCategoriasProveedorDami3d extends ListRecords { protected static string $resource=CategoriaProveedorDami3dResource::class; protected function getHeaderActions():array{return [CreateAction::make()->label('Nueva categoría')];} }
