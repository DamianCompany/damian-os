<?php

namespace App\Filament\Resources\DamiOrders\Pages;

use App\Filament\Resources\DamiOrders\Concerns\SyncsDamiOrderFiles;
use App\Filament\Resources\DamiOrders\DamiOrderResource;
use App\Filament\Resources\DamiOrders\Schemas\DamiOrderForm;
use App\Models\DamiOrder;
use Filament\Resources\Pages\Concerns\HasWizard;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Component;

class CreateDamiOrder extends CreateRecord
{
    use HasWizard {
        getWizardComponent as getBaseWizardComponent;
    }
    use SyncsDamiOrderFiles;

    protected static string $resource = DamiOrderResource::class;

    protected static ?string $title = 'Nuevo pedido DAMI 3D';

    public function getSubheading(): ?string
    {
        return 'Registra una etapa a la vez.';
    }

    public function getSteps(): array
    {
        return DamiOrderForm::steps();
    }

    public function getWizardComponent(): Component
    {
        return $this->getBaseWizardComponent()
            ->persistStepInQueryString('etapa')
            ->extraAttributes(['class' => 'damian-order-wizard']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->extractFileData($data);
    }

    protected function afterCreate(): void
    {
        /** @var DamiOrder $order */
        $order = $this->record;
        $this->syncOrderFiles($order);
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
