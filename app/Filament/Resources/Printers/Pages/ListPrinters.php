<?php

namespace App\Filament\Resources\Printers\Pages;

use App\Filament\Resources\Printers\PrinterResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrinters extends ListRecords
{
    protected static string $resource = PrinterResource::class;

    public function getSubheading(): ?string
    {
        return 'Administra el inventario de equipos. El responsable se asignará en cada pedido, no en la impresora.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Agregar impresora')->icon('heroicon-o-plus'),
        ];
    }
}
