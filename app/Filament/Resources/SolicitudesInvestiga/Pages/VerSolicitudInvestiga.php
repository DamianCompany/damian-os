<?php

namespace App\Filament\Resources\SolicitudesInvestiga\Pages;

use App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Size;

class VerSolicitudInvestiga extends ViewRecord
{
    protected static string $resource = SolicitudInvestigaResource::class;

    protected string $view = 'filament.resources.solicitudes-investiga.pages.ver-solicitud-investiga';

    public function getTitle(): string
    {
        return "Solicitud {$this->getRecord()->codigo}";
    }

    public function getSubheading(): ?string
    {
        return 'Ficha inicial y trazabilidad de InvestigaLab';
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
                ->color('primary')
                ->size(Size::Large)
                ->url(static::getResource()::getUrl('index')),
        ];
    }
}
