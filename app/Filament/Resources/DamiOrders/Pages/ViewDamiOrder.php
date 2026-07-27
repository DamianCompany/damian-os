<?php

namespace App\Filament\Resources\DamiOrders\Pages;

use App\Filament\Resources\DamiOrders\Actions\DamiOrderWorkflowActions;
use App\Filament\Resources\DamiOrders\DamiOrderResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Size;
use Illuminate\Support\Facades\Storage;

class ViewDamiOrder extends ViewRecord
{
    protected static string $resource = DamiOrderResource::class;

    protected string $view = 'filament.resources.dami-orders.pages.view-dami-order';

    public function getTitle(): string
    {
        return "Pedido {$this->getRecord()->order_number}";
    }

    public function getSubheading(): ?string
    {
        return 'Ficha completa de trazabilidad DAMI 3D';
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('backToList')
                ->label('Regresar al listado')
                ->icon('heroicon-o-arrow-left')
                ->color('primary')
                ->size(Size::Large)
                ->url(static::getResource()::getUrl('index')),
            DamiOrderWorkflowActions::start(),
            DamiOrderWorkflowActions::updateProduction(),
            DamiOrderWorkflowActions::complete(),
            EditAction::make()
                ->label('Corregir registro')
                ->visible(fn (): bool => auth()->user()?->role === 'dami_3d' && $this->getRecord()->status === 'pending'),
        ];
    }

    public function getFileUrl(?string $path): ?string
    {
        if (blank($path) || ! Storage::disk('local')->exists($path)) {
            return null;
        }

        return Storage::disk('local')->temporaryUrl($path, now()->addMinutes(15));
    }
}
