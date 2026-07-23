<?php

namespace App\Filament\Resources\DamiOrders\Pages;

use App\Filament\Resources\DamiOrders\DamiOrderResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDamiOrders extends ListRecords
{
    protected static string $resource = DamiOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
