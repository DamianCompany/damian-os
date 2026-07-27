<?php

namespace App\Filament\Resources\DamiOrders\Pages;

use App\Filament\Resources\DamiOrders\DamiOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDamiOrders extends ListRecords
{
    protected static string $resource = DamiOrderResource::class;

    public function getSubheading(): ?string
    {
        return auth()->user()?->role === 'gerencia'
            ? 'Consulta cada pedido de DAMI 3D y abre su ficha para revisar la trazabilidad completa.'
            : 'Gestiona los pedidos, su avance y la información de producción.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Crear pedido')
                ->icon('heroicon-o-plus')
                ->visible(fn (): bool => auth()->user()?->role === 'dami_3d'),
        ];
    }
}
