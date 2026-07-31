<?php
namespace App\Filament\Resources\ProveedoresDami3d\Pages;
use App\Filament\Resources\ProveedoresDami3d\ProveedorDami3dResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
class ListarProveedoresDami3d extends ListRecords { protected static string $resource=ProveedorDami3dResource::class; protected function getHeaderActions(): array { return [CreateAction::make()->label('Registrar proveedor')->icon('heroicon-o-plus')]; } protected function getHeaderWidgets(): array { return [\App\Filament\Resources\ProveedoresDami3d\Widgets\ResumenProveedoresDami3d::class]; } public function getSubheading(): ?string { return 'Contactos, materiales y precios de abastecimiento del área.'; } }
