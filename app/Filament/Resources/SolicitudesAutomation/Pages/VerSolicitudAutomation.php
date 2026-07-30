<?php

namespace App\Filament\Resources\SolicitudesAutomation\Pages;

use App\Filament\Resources\SolicitudesAutomation\SolicitudAutomationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Size;

class VerSolicitudAutomation extends ViewRecord
{
    protected static string $resource = SolicitudAutomationResource::class;

    protected string $view = 'filament.resources.solicitudes-automation.pages.ver-solicitud-automation';

    public function getTitle(): string
    {
        return "Proyecto {$this->getRecord()->codigo}";
    }

    public function getSubheading(): ?string
    {
        return 'Expediente y trazabilidad de Damian Automation';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('gestionar')
                ->label('Continuar proyecto')
                ->icon('heroicon-o-pencil-square')
                ->color('success')
                ->size(Size::Large)
                ->visible(fn (): bool => static::getResource()::canEdit($this->getRecord()))
                ->url(static::getResource()::getUrl('edit', ['record' => $this->getRecord()])),
            Action::make('regresar')
                ->label('Regresar al listado')
                ->icon('heroicon-o-arrow-left')
                ->size(Size::Large)
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
