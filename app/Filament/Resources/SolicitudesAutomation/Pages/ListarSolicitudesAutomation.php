<?php

namespace App\Filament\Resources\SolicitudesAutomation\Pages;

use App\Filament\Resources\SolicitudesAutomation\SolicitudAutomationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListarSolicitudesAutomation extends ListRecords
{
    protected static string $resource = SolicitudAutomationResource::class;

    protected static ?string $title = 'Solicitudes y proyectos';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nueva solicitud')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => static::getResource()::canCreate()),
        ];
    }
}
