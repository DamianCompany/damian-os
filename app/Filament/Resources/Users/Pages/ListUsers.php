<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    public function getSubheading(): ?string
    {
        return 'Crea, habilita o suspende accesos sin eliminar el historial de las personas.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva credencial')->icon('heroicon-o-user-plus'),
        ];
    }
}
