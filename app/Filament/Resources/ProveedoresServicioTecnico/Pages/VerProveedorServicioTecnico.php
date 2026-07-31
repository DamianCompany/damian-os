<?php

namespace App\Filament\Resources\ProveedoresServicioTecnico\Pages;

use App\Filament\Resources\ProveedoresServicioTecnico\ProveedorServicioTecnicoResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class VerProveedorServicioTecnico extends ViewRecord
{
    protected static string $resource = ProveedorServicioTecnicoResource::class;
    protected string $view = 'filament.resources.proveedores-servicio-tecnico.pages.ver-proveedor-servicio-tecnico';

    public function getTitle(): string
    {
        return 'Ficha del proveedor';
    }

    public function getSubheading(): ?string
    {
        return $this->record->codigo.' · '.$this->record->razon_social;
    }

    public function getBreadcrumb(): string
    {
        return 'Ficha';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('whatsapp')
                ->label('Contactar por WhatsApp')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('success')
                ->visible(fn (): bool => filled($this->record->whatsapp))
                ->url(fn (): string => 'https://wa.me/'.preg_replace('/\D+/', '', $this->record->whatsapp))
                ->openUrlInNewTab(),
            Action::make('editar')
                ->label('Editar proveedor')
                ->icon('heroicon-o-pencil-square')
                ->visible(fn (): bool => auth()->user()?->role === 'servicio_tecnico')
                ->url(static::getResource()::getUrl('edit', ['record' => $this->record])),
            Action::make('regresar')
                ->label('Regresar')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(static::getResource()::getUrl()),
        ];
    }
}
