<?php
namespace App\Filament\Resources\ProveedoresDami3d\Pages;
use App\Filament\Resources\ProveedoresDami3d\ProveedorDami3dResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
class EditarProveedorDami3d extends EditRecord { protected static string $resource=ProveedorDami3dResource::class; protected static ?string $title='Editar proveedor'; protected function getHeaderActions(): array { return [Action::make('volver')->label('Volver a la ficha')->icon('heroicon-o-arrow-left')->url(static::getResource()::getUrl('view',['record'=>$this->record]))]; } protected function getRedirectUrl(): string { return static::getResource()::getUrl('view',['record'=>$this->record]); } }
