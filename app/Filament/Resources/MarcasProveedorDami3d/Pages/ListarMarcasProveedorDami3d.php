<?php
namespace App\Filament\Resources\MarcasProveedorDami3d\Pages; use App\Filament\Resources\MarcasProveedorDami3d\MarcaProveedorDami3dResource; use Filament\Actions\CreateAction; use Filament\Resources\Pages\ListRecords;
class ListarMarcasProveedorDami3d extends ListRecords { protected static string $resource=MarcaProveedorDami3dResource::class; protected function getHeaderActions():array{return [CreateAction::make()->label('Nueva marca')];} }
