<?php

namespace App\Filament\Resources\SolicitudesInvestiga\Pages;

use App\Filament\Resources\SolicitudesInvestiga\SolicitudInvestigaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListarSolicitudesInvestiga extends ListRecords
{
    protected static string $resource = SolicitudInvestigaResource::class;

    public function getSubheading(): ?string
    {
        return auth()->user()?->role === 'gerencia'
            ? 'Consulta las iniciativas registradas y su trazabilidad.'
            : 'Registra ideas en pocos minutos y revisa su avance.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva idea')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => auth()->user()?->role === 'investiga_lab'),
        ];
    }
}
