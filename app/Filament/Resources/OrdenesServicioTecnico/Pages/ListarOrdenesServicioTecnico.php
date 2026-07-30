<?php

namespace App\Filament\Resources\OrdenesServicioTecnico\Pages;

use App\Filament\Resources\OrdenesServicioTecnico\OrdenServicioTecnicoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListarOrdenesServicioTecnico extends ListRecords
{
    protected static string $resource = OrdenServicioTecnicoResource::class;
    protected static ?string $title = 'Órdenes de servicio';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo ingreso')->icon('heroicon-o-plus')
                ->visible(fn (): bool => static::getResource()::canCreate()),
        ];
    }
}
