<?php
namespace App\Filament\Resources\ProveedoresDami3d\Pages;
use App\Filament\Resources\ProveedoresDami3d\ProveedorDami3dResource;
use Filament\Resources\Pages\CreateRecord;
class CrearProveedorDami3d extends CreateRecord { protected static string $resource=ProveedorDami3dResource::class; protected static ?string $title='Registrar proveedor'; protected function getRedirectUrl(): string { return static::getResource()::getUrl('view',['record'=>$this->record]); } }
