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
        return auth()->user()?->role === 'gerencia'
            ? 'Administra el inventario de equipos y su ubicación.'
            : 'Consulta la ubicación y disponibilidad actual de los equipos.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Agregar impresora')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => auth()->user()?->role === 'gerencia'),
        ];
    }
}
