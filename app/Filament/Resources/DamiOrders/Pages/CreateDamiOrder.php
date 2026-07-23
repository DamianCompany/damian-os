<?php

namespace App\Filament\Resources\DamiOrders\Pages;

use App\Filament\Resources\DamiOrders\DamiOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDamiOrder extends CreateRecord
{
    protected static string $resource = DamiOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
